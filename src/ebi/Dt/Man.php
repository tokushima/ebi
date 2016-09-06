<?php
namespace ebi\Dt;
/**
 * ドキュメントの取得
 * @author tokushima
 */
class Man{
	public static function get_reflection_source(\ReflectionClass $r){
		return implode(array_slice(file($r->getFileName()),$r->getStartLine(),($r->getEndLine()-$r->getStartLine()-1)));
	}
	public static function get_conf_list(\ReflectionClass $r,$src=null){
		if(empty($src)){
			$src = self::get_reflection_source($r);
		}
		$conf_list = [];
		if(preg_match_all("/Conf::gets\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[2] as $k => $v){
				self::get_desc($conf_list,$match,$k,$v[0],$src,$r->getName());
			}
		}		
		if(preg_match_all("/Conf::get\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[2] as $k => $v){
				self::get_desc($conf_list,$match,$k,$v[0],$src,$r->getName());
			}
		}
		if(preg_match_all("/self::get_self_conf_get\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[2] as $k => $v){
				self::get_desc($conf_list,$match,$k,$v[0],$src,$r->getName());
			}
		}		
		return $conf_list;
	}
	public static function entry_description($entry){
		return (preg_match('/\/\*\*.+?\*\//s',\ebi\Util::file_read($entry),$m)) ?
		trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(['/'.'**','*'.'/'],'',$m[0]))) :
		'';
	}
	private static function get_method_document(\ReflectionMethod $method){
		$method_document = $method->getDocComment();
		
		if($method_document === false){
			$p = $method->getDeclaringClass()->getParentClass();
				
			while($p !== false){
				try{
					$method_document = $p->getMethod($method->getName())->getDocComment();
						
					if($method_document !== false){
						break;
					}
					$p = $p->getParentClass();
				}catch(\ReflectionException $e){
					break;
				}
			}
		}
		return self::trim_doc($method_document);
	}
	/**
	 * クラスのドキュメント
	 * @param string $class
	 */
	public static function class_info($class){
		$r = new \ReflectionClass(str_replace(['.','/'],['\\','\\'],$class));
		
		if($r->getFilename() === false || !is_file($r->getFileName())){
			throw new \ebi\exception\InvalidArgumentException('`'.$class.'` file not found.');
		}
		$traits = [];
		$parent = new \ReflectionClass($r->getName());
		
		while(true){
			$traits = array_merge($traits,$parent->getTraitNames());
			if(($parent = $parent->getParentClass()) === false){
				break;
			}
		}
		$src = self::get_reflection_source($r);
		$document = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$r->getDocComment())));
		$extends = ($r->getParentClass() === false) ? null : $r->getParentClass()->getName();
		$updated = filemtime($r->getFilename());
		
		if(basename($r->getFilename(),'.php') === basename(dirname($r->getFilename()))){
			foreach(\ebi\Util::ls(dirname($r->getFilename())) as $f){
				if(($u = filemtime($f->getPathname())) > $updated){
					$updated = $u;
				}
			}
		}
		$methods = $static_methods = $protected_methods = $protected_static_methods = [[],[]];
		$see_class = $see_method = $see_url = [];
		$plugin_method = [];
		
		foreach($r->getMethods() as $method){
			if(substr($method->getName(),0,1) != '_' && ($method->isPublic() || $method->isProtected())){
				$method_document = self::get_method_document($method);
				list($method_description) = explode("\n",trim(preg_replace('/@.+/','',$method_document)));
				
				if(strpos($method_description,'non-PHPdoc') !== false){
					if(preg_match("/@see\s+(.*)/",$method_document,$match)){
						$method_description = str_replace("\\",'.',trim($match[1]));
						if(preg_match("/^.+\/([^\/]+)$/",$method_description,$m)){
							$method_description = trim($m[1]);
						}
						if(substr($method_description,0,1) == '.'){
							$method_description = substr($method_description,1);
						}
						if(strpos($method_description,'::') !== false){
							list($c,$m) = explode('::',str_replace(['(',')'],'',$method_description));
							try{
								$i = self::method_info($c,$m);
								list($method_description) = explode("\n",$i['description']);
							}catch(\Exception $e){
								$method_description = '@see '.$method_description;
							}
						}
					}
				}				
				
				if(preg_match_all("/@plugin\s+([\w\.\\\\]+)/",$method_document,$match)){
					foreach($match[1] as $v){
						$plugin_method[trim($v)][] = $method->getName();
					}
				}else{
					$dec = ($method->getDeclaringClass()->getFileName() == $r->getFileName()) ? 0 : 1;
					
					if($method->isStatic()){
						if($method->isPublic()){
							$static_methods[$dec][$method->getName()] = $method_description;
						}else{
							$protected_static_methods[$dec][$method->getName()] = $method_description;								
						}
					}else{
						if($method->isPublic()){
							$methods[$dec][$method->getName()] = $method_description;
						}else{
							$protected_methods[$dec][$method->getName()] = $method_description;
						}
					}
				}
			}
		}
		$plugins = [];
		if(preg_match_all("/->get_object_plugin_funcs\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[2] as $k => $v){
				self::get_desc($plugins,$match,$k,$v[0],$src,$class);
			}
		}
		if(preg_match_all("/->call_object_plugin_funcs\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[2] as $k => $v){
				self::get_desc($plugins,$match,$k,$v[0],$src,$class);
			}
		}
		if(preg_match_all("/::call_class_plugin_funcs\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
			foreach($match[2] as $k => $v){
				self::get_desc($plugins,$match,$k,$v[0],$src,$class);
			}
		}
		$added_plugins = [];
		if(in_array('ebi\\Plugin',$traits)){
			foreach(\ebi\Conf::get_class_plugin($r->getName()) as $o){
				if(is_object($o)){
					$added_plugins[] = get_class($o);
				}else{
					$added_plugins[] = $o;
				}
			}
		}
		
		$conf = self::get_conf_list($r,$src);
		$properties = [];
		$anon = \ebi\Annotation::get_class(str_replace(['.','/'],['\\','\\'],$class),'var','summary');
		
		foreach($r->getProperties() as $prop){
			if(!$prop->isPrivate()){
				$name = $prop->getName();
				
				if($name[0] != '_' && !$prop->isStatic()){
					$properties[$name] = [
						(isset($anon[$name]['type']) ? self::type($anon[$name]['type'],$class) : 'mixed')
						,(isset($anon[$name]['summary']) ? $anon[$name]['summary'] : null)
						,!(isset($anon[$name]['hash']) && $anon[$name]['hash'] === false)
					];
				}
			}
		}
		
		if(preg_match_all("/@see\s+([\w\.\:\\\\]+)/",$document,$match)){
			foreach($match[1] as $v){
				if(strpos($v,'::') !== false){
					$see_method[$v] = explode('::',trim($v),2);
				}else if(substr($v,-1) != ':'){
					$v = trim($v);
					$see_class[$v] = $v;
				}
			}
		}
		ksort($see_class);
		ksort($see_method);
			
		if(preg_match_all("/@see\s+(\w+:\/\/.+)/",$document,$match)){
			foreach($match[1] as $v){
				$v = trim($v);
				$see_url[$v] = $v;
			}
		}
		$description = trim(preg_replace('/@.+/','',$document));
				
		ksort($static_methods[0]);
		ksort($methods[0]);
		ksort($protected_methods[0]);
		ksort($protected_static_methods[0]);
		ksort($static_methods[1]);
		ksort($methods[1]);
		ksort($protected_methods[1]);
		ksort($protected_static_methods[1]);
		ksort($plugins);
		
		return [
			'filename'=>$r->getFileName(),
			'extends'=>$extends,
			'abstract'=>$r->isAbstract(),
			'version'=>date('Ymd',$updated),
			'static_methods'=>$static_methods[0],
			'methods'=>$methods[0],
			'protected_static_methods'=>$protected_static_methods[0],
			'protected_methods'=>$protected_methods[0],
			'inherited_static_methods'=>$static_methods[1],
			'inherited_methods'=>$methods[1],
			'inherited_protected_static_methods'=>$protected_static_methods[1],
			'inherited_protected_methods'=>$protected_methods[1],
			'plugin_method'=>$plugin_method,
			'properties'=>$properties,
			'package'=>$class,
			'description'=>$description,
			'plugins'=>$plugins,
			'added_plugins'=>$added_plugins,
			'conf_list'=>$conf,
			'see_class'=>$see_class,
			'see_method'=>$see_method,
			'see_url'=>$see_url,				
		];
	}
	/**
	 * クラスドメソッドのキュメント
	 * @param string $class
	 * @param string $method
	 * @param boolean $deep 
	 */
	public static function method_info($class,$method,$deep=false){
		$ref = new \ReflectionMethod(str_replace(['.','/'],['\\','\\'],$class),$method);
		$params = $return = $plugins = $see_class = $see_method = $see_url = $request = $context = $args = $throws = [];
		$document = $src = $http_method = null;
		$deprecated = false;
		$is_request_flow = $ref->getDeclaringClass()->isSubclassOf('\ebi\flow\Request');
		
		if(is_file($ref->getDeclaringClass()->getFileName())){
			$src = self::method_src($ref);
			$document = self::get_method_document($ref);
			$deprecated = (strpos($ref->getDocComment(),'@deprecated') !== false);
			$use_method_list = ($deep) ? self::use_method_list($ref->getDeclaringClass()->getName(),$ref->getName()) :
											[$ref->getDeclaringClass().'::'.$method];
			
			if($is_request_flow){
				try{
					$ref = new \ReflectionMethod(str_replace(['.','/'],['\\','\\'],$class),'__before__');
					
					if(preg_match_all('/@.+$/',self::get_method_document($ref),$m)){
						foreach($m[0] as $a){
							$document = $a.PHP_EOL.$document;
						}
					}
				}catch(\ReflectionException $e){
				}
			}
											
			if(preg_match("/@http_method\s+([^\s]+)/",$document,$match)){
				$http_method = strtoupper(trim($match[1]));
			}else{
				$http_method = (
					(strpos($src,'$this->is_post()') !== false) && 
					(strpos($src,'!$this->is_post()') === false)
				) ? ' POST' : null;
			}
			
			if(preg_match("/@return\s+([^\s]+)(.*)/",$document,$match)){
				// type, summary
				$return[] = [self::type(trim($match[1]),$class),trim($match[2])];
			}
			foreach($ref->getParameters() as $p){
				$params[$p->getName()] = [
					// type, is_ref, has_default, default, summary
					'mixed'
					,$p->isPassedByReference()
					,$p->isDefaultValueAvailable()
					,($p->isDefaultValueAvailable() ? $p->getDefaultValue() : null)
					,null
				];
			}
			if(preg_match_all("/@param\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){
				foreach($match[0] as $k => $v){
					if(isset($params[$match[2][$k]])){
						$params[$match[2][$k]][0] = self::type($match[1][$k],$class);
						$params[$match[2][$k]][4] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
					}
				}
			}
			if(preg_match_all('/->in_vars\((["\'])(.+?)\\1/',$src,$match)){
				foreach($match[2] as $n){
					$request[$n] = ['mixed',null];
				}
			}
			
			if(preg_match_all("/@request\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){
				foreach($match[0] as $k => $v){
					$request[$match[2][$k]][0] = self::type($match[1][$k],$class);
					$request[$match[2][$k]][1] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
					$request[$match[2][$k]][2] = false; // require
					
					if(strpos($request[$match[2][$k]][1],'@[') !== false){
						list($request[$match[2][$k]][1],$anon) = explode('@[',$request[$match[2][$k]][1],2);
						$a = \ebi\Annotation::activation('@['.$anon);
						$request[$match[2][$k]][2] = isset($a['require']) ? $a['require'] : false;
					}
				}
			}
			if(preg_match_all("/@context\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){
				foreach($match[0] as $k => $v){
					$context[$match[2][$k]][0] = self::type($match[1][$k],$class);
					$context[$match[2][$k]][1] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
				}
			}
			if(preg_match_all("/@arg\s+([^\s]+)\s+\\$(\w+)(.*)/",$document,$match)){
				foreach($match[0] as $k => $v){
					$args[$match[2][$k]] = (isset($match[3][$k]) ? $match[3][$k] : 'null');
				}
			}
			foreach($use_method_list as $class_method){
				list($uclass,$umethod) = explode('::',$class_method);
				try{
					$ref = new \ReflectionMethod($uclass,$umethod);
					$use_method_src = self::method_src($ref);
					$use_method_doc = self::trim_doc($ref->getDocComment());
					$method_fullname = $ref->getDeclaringClass()->getName().'::'.$ref->getName();
					
					if(preg_match_all("/throw\s+new\s+([\\\\\w]+)\((.*)\)/",$use_method_src,$match)){
						foreach($match[1] as $k => $n){
							$n = trim($n);
							$throws[$n] = [$n,null,$method_fullname];
						}
					}
					if(preg_match_all("/\\\\ebi\\\\Exceptions::add\(\s*new\s+([\\\\\w]+)\((.*)\)/",$use_method_src,$match)){
						foreach($match[1] as $k => $n){
							$n = trim($n);
							$throws[$n] = [$n,null,$method_fullname];
						}
					}
					if(preg_match_all("/@throws\s+([^\s]+)(.*)/",$use_method_doc,$match)){
						foreach($match[1] as $k => $n){
							$n = trim($n);
							$throws[$n] = [$n,trim($match[2][$k]),$method_fullname];
						}
					}
				}catch(\ReflectionException $e){
				}
			}
			foreach($throws as $k => $info){
				if(empty($throws[$k][1])){
					try{
						$ref = new \ReflectionClass($info[0]);
						$throws[$k][1] = trim(preg_replace('/@.+/','',trim(preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$ref->getDocComment())))));
					}catch(\ReflectionException $e){
					}
				}
			}
			ksort($throws);
			
			if(preg_match_all("/@plugin\s+([\w\.\\\\]+)/",$document,$match)){
				foreach($match[1] as $v){
					$plugins[trim($v)] = true;
				}
			}
			$plugins = array_keys($plugins);
			sort($plugins);
			
			if(preg_match_all("/@see\s+([\w\.\:\\\\]+)/",$document,$match)){
				foreach($match[1] as $v){
					if(strpos($v,'::') !== false){
						$see_method[$v] = explode('::',trim($v),2);
					}else if(substr($v,-1) != ':'){
						$v = trim($v);
						$see_class[$v] = $v;
					}
				}
			}
			ksort($see_class);
			ksort($see_method);
			
			if(preg_match_all("/@see\s+(\w+:\/\/.+)/",$document,$match)){
				foreach($match[1] as $v){
					$v = trim($v);
					$see_url[$v] = $v;
				}
			}
		}
		$description = trim(preg_replace('/@.+/','',$document));
		
		return [
			'package'=>$class,
			'method_name'=>$method,
			'params'=>$params,
			'request'=>$request,
			'context'=>$context,
			'args'=>$args,
			'return'=>$return,
			'description'=>$description,
			'throws'=>$throws,
			'http_method'=>$http_method,
			'deprecated'=>$deprecated,
			'plugins'=>$plugins,
			'see_class'=>$see_class,
			'see_method'=>$see_method,
			'see_url'=>$see_url,
		];
	}
	private static function type($type,$class){
		if($type == 'self' || $type == '$this'){
			$type = $class;
		}
		$type = str_replace('\\','.',$type);
		
		if(substr($type,0,1) == '.'){
			$type = substr($type,1);
		}
		return $type;
	}
	private static function	get_desc(&$arr,$match,$k,$name,$src,$class){
		if(!isset($arr[$name])){
			$arr[$name] = [null,[],[]];
		}
		$doc = substr($src,0,$match[0][$k][1]);
		$doc = trim(substr($doc,0,strrpos($doc,PHP_EOL)));
		
		if(substr($doc,-2) == '*'.'/'){
			$desc = '';
			foreach(array_reverse(explode(PHP_EOL,$doc)) as $line){
				if(strpos(ltrim($line),'/'.'**') !== 0){
					$desc = $line.PHP_EOL.$desc;
				}else{
					$desc = substr($line,strpos($line,'/**')+3).PHP_EOL.$desc;
					break;
				}
			}
			$desc = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace('*'.'/','',$desc)));
			if(preg_match_all("/@param\s+([^\s]+)\s+\\$(\w+)(.*)/",$desc,$m)){
				foreach(array_keys($m[2]) as $n){
					$arr[$name][1][$m[2][$n]] = [$m[2][$n],self::type($m[1][$n],$class),trim($m[3][$n])];
				}
			}
			if(preg_match("/@return\s+([^\s]+)(.*)/",$desc,$m)){
				$arr[$name][2] = [self::type(trim($m[1]),$class),trim($m[2])];
			}
			$arr[$name][0] = trim(preg_replace('/@.+/','',$desc));
		}
		return $arr;
	}
	private static function method_src(\ReflectionMethod $ref){
		if(is_file($ref->getDeclaringClass()->getFileName())){
			return implode(array_slice(file($ref->getDeclaringClass()->getFileName()),$ref->getStartLine(),($ref->getEndLine()-$ref->getStartLine()-1)));
		}
		return '';
	}
	private static function trim_doc($doc){
		return trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(['/'.'**','*'.'/'],'',$doc)));
	}
	private static function use_method_list($class,$method,&$loaded_method_src=[]){
		$list = [];
	
		try{
			$ref = new \ReflectionMethod($class,$method);
			$kname = $ref->getDeclaringClass()->getName().'::'.$ref->getName();
			if(isset($loaded_method_src[$kname])) return [];
			$loaded_method_src[$kname] = true;
			$list[$kname] = true;
	
			if(is_file($ref->getDeclaringClass()->getFileName())){
				$src = self::method_src($ref);
				$vars = ['$this'=>$class];
	
				if(preg_match_all('/(\$\w+)\s*=\s*new\s+([\\\\\w]+)/',$src,$m)){
					foreach($m[1] as $k => $v){
						$vars[$v] = $m[2][$k];
					}
				}
				if(preg_match_all('/(\$\w+)\s*=\s*([\\\\\w]+)::(\w+)/',$src,$m)){
					foreach($m[1] as $k => $v){
						try{
							$ref = new \ReflectionMethod($m[2][$k],$m[3][$k]);
							if(preg_match("/@return\s+([^\s]+)(.*)/",self::trim_doc($ref->getDocComment()),$r)){
								if(preg_match('/A-Z/',$r[1])){
									$vars[$v] = $r[1];
								}else{
									$vars[$v] = $m[2][$k];
								}
							}
						}catch(\ReflectionException $e){
						}
					}
				}				
				if(preg_match_all('/(\$\w+)->(\w+)/',$src,$m)){
					foreach($m[1] as $k => $v){
						if(isset($vars[$v])){
							$list[$vars[$v].'::'.$m[2][$k]] = true;
						}
					}
				}
				if(preg_match_all('/([\\\\\w]+)::(\w+)/',$src,$m)){
					foreach($m[1] as $k => $v){
						if($v == 'self' || $v == 'static'){
							$v = $class;
						}
						$list[$v.'::'.$m[2][$k]] = true;
					}
				}
				foreach(array_keys($list) as $mcm){
					if(!isset($loaded_method_src[$mcm])){
						list($c,$m) = explode('::',$mcm);
	
						foreach(self::use_method_list($c,$m,$loaded_method_src) as $k){
							$list[$k] = true;
						}
					}
				}
			}
		}catch(\ReflectionException $e){
		}
		return array_keys($list);
	}
}
