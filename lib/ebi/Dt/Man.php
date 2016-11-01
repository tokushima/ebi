<?php
namespace ebi\Dt;
/**
 * ドキュメントの取得
 * @author tokushima
 */
class Man{
	private static function get_reflection_source(\ReflectionClass $r){
		return implode(array_slice(
			file($r->getFileName()),
			$r->getStartLine(),
			($r->getEndLine()-$r->getStartLine()-1)
		));
	}
	/**
	 * \ebi\Conf:get
	 * @param \ReflectionClass $r
	 * @param string $src
	 */
	public static function get_conf_list(\ReflectionClass $r,$src=null){
		if(empty($src)){
			$src = self::get_reflection_source($r);
		}
		$conf_list = [];
		
		foreach([
			"/Conf::gets\(([\"\'])(.+?)\\1/"=>'mixed[]',
			"/Conf::get\(([\"\'])(.+?)\\1/"=>'mixed',
			"/self::get_self_conf_get\(([\"\'])(.+?)\\1/"=>'mixed',
		] as $preg => $default_type){
			if(preg_match_all($preg,$src,$m,PREG_OFFSET_CAPTURE)){
				foreach($m[2] as $k => $v){
					// 呼び出しが重複したら先にドキュメントがあった方
					if(!array_key_exists($v[0],$conf_list) || !$conf_list[$v[0]]->has_params()){
						$conf_list[$v[0]] = \ebi\man\DocInfo::parse($v[0],$src,$m[0][$k][1]);
						
						if(!$conf_list[$v[0]]->has_params()){
							$conf_list[$v[0]]->add_params(new \ebi\man\DocParam('val',$default_type));
						}
						$conf_list[$v[0]]->set_opt('def',\ebi\Conf::exists($r->getName(),$v[0]));
					}
				}
			}			
		}
		ksort($conf_list);
		return $conf_list;
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
		$info = new \ebi\man\DocInfo();
		$r = new \ReflectionClass(self::get_class_name($class));
		
		if($r->getFilename() === false || !is_file($r->getFileName())){
			throw new \ebi\exception\InvalidArgumentException('`'.$class.'` file not found.');
		}
		$src = self::get_reflection_source($r);
		$document = self::trim_doc($r->getDocComment());
		
		$info->name($r->getName());
		$info->document(trim(preg_replace('/@.+/','',$document)));
		
		$info->set_opt('filename',$r->getFileName());
		$info->set_opt('extends',(($r->getParentClass() === false) ? null : $r->getParentClass()->getName()));
		$info->set_opt('abstract',$r->isAbstract());
		
		$see = [];
		if(preg_match_all("/@see\s+([\w\.\:\\\\]+)/",$document,$m)){
			foreach($m[1] as $v){
				$v = trim($v);
				
				if(strpos($v,'://') !== false){
					$see[$v] = ['type'=>'url','url'=>$v];					
				}else if(strpos($v,'::') !== false){
					list($class,$method) = explode('::',2);
					$see[$v] = ['type'=>'method','class'=>$class,'method'=>$method];
				}else if(substr($v,-1) != ':'){
					$see[$v] = ['type'=>'class','class'=>$class];
				}
			}
		}		
		
		$methods = [];
		foreach($r->getMethods() as $method){
			if(substr($method->getName(),0,1) != '_' && $method->isPublic() && !$method->isStatic()){
				$ignore = ['getIterator'];
				
				if(!in_array($method->getName(),$ignore)){
					$method_document = self::get_method_document($method);
					list($desc) = explode(PHP_EOL,trim(preg_replace('/@.+/','',$method_document)));
					
					$method_info = new \ebi\man\DocInfo();
					$method_info->name($method->getName());
					$method_info->document($desc);
					$methods[] = $method_info;
				}
			}
		}
		$info->set_opt('methods',$methods);
		
		$properties = [];
		$anon = \ebi\Annotation::get_class(self::get_class_name($class),'var','summary');
		$is_obj = $r->isSubclassOf(\ebi\Object::class);
		foreach($r->getProperties() as $prop){
			if($prop->isPublic() || ($is_obj && $prop->isProtected())){
				$name = $prop->getName();
				
				if($name[0] != '_' && !$prop->isStatic()){
					$properties[$name] = new \ebi\man\DocParam(
						$name,
						(isset($anon[$name]['type']) ? $anon[$name]['type'] : 'mixed'),
						(isset($anon[$name]['summary']) ? $anon[$name]['summary'] : null),
						['hash'=>($prop->isPublic() || !(isset($anon[$name]['hash']) && $anon[$name]['hash'] === false))]
					);
				}
			}
		}
		$info->set_opt('properties',$properties);
		
		$call_plugins = [];
		foreach([
			"/->get_object_plugin_funcs\(([\"\'])(.+?)\\1/",
			"/->call_object_plugin_funcs\(([\"\'])(.+?)\\1/",
			"/::call_class_plugin_funcs\(([\"\'])(.+?)\\1/",
		] as $preg){
			if(preg_match_all($preg,$src,$m,PREG_OFFSET_CAPTURE)){
				foreach($m[2] as $k => $v){
					$call_plugins[$v[0]] = \ebi\man\DocInfo::parse($v[0], $src, $m[0][$k][1]);
					$call_plugins[$v[0]]->set_opt('added',[]);
				}
			}
		}		
		
		$traits = [];
		$parent = new \ReflectionClass($r->getName());
		do{
			$traits = array_merge($traits,$parent->getTraitNames());
			if(($parent = $parent->getParentClass()) === false){
				break;
			}
		}while(true);
		
		if(in_array('ebi\\Plugin',$traits)){
			foreach(\ebi\Conf::get_class_plugin($r->getName()) as $o){
				$pr = new \ReflectionClass(is_object($o) ? get_class($o) : $o);

				foreach($pr->getMethods(\ReflectionMethod::IS_PUBLIC) as $m){
					foreach(array_keys($call_plugins) as $method_name){
						if($m->getName() == $method_name){
							$added = $call_plugins[$method_name]->opt('added');
							$added[] = $pr->getName();
							$call_plugins[$method_name]->set_opt('added',$added);
						}
					}
				}
			}
		}
		$info->set_opt('plugins',$call_plugins);
		$info->set_opt('conf_list',self::get_conf_list($r,$src));
				
		return $info;
	}
	private static function get_class_name($class){
		return str_replace(['.','/'],['\\','\\'],$class);
	}
	/**
	 * クラスドメソッドのキュメント
	 * @param string $class
	 * @param string $method
	 */
	public static function method_info($class,$method){
		$ref = new \ReflectionMethod(self::get_class_name($class),$method);
		$is_request_flow = $ref->getDeclaringClass()->isSubclassOf('\ebi\flow\Request');
		$method_fullname = $ref->getDeclaringClass()->getName().'::'.$ref->getName();
		
		if(is_file($ref->getDeclaringClass()->getFileName())){
			$document = '';
			if($is_request_flow){
				try{
					$ref = new \ReflectionMethod(self::get_class_name($class),'__before__');
			
					if(preg_match_all('/@.+$/',self::get_method_document($ref),$m)){
						foreach($m[0] as $a){
							$document = $a.PHP_EOL.$document;
						}
					}
				}catch(\ReflectionException $e){
				}
			}
			$document = $document.self::get_method_document($ref);
			$src = self::method_src($ref);

			$info = \ebi\man\DocInfo::parse($method_fullname,$document);
			$info->set_opt('deprecated',(strpos($document,'@deprecated') !== false));
			
			if(preg_match("/@http_method\s+([^\s]+)/",$document,$match)){
				$info->set_opt('http_method',strtoupper(trim($match[1])));
			}else{
				$info->set_opt('http_method',(
					(strpos($src,'$this->is_post()') !== false) && 
					(strpos($src,'!$this->is_post()') === false)
				) ? ' POST' : null);
			}
			$info->set_opt('class',$ref->getDeclaringClass()->getName());
			$info->set_opt('method',$ref->getName());
			$info->set_opt('requests',\ebi\man\DocParam::parse('request',$document));
			$info->set_opt('contexts',\ebi\man\DocParam::parse('context',$document));
			$info->set_opt('args',\ebi\man\DocParam::parse('arg',$document));

			if(!$info->is_return() && $info->has_opt('contexts')){
				$info->return(new \ebi\man\DocParam('return','mixed{}'));
			}

			$throws = $throw_param = [];
			foreach(self::use_method_list($ref->getDeclaringClass()->getName(),$ref->getName()) as $class_method){
				list($uclass,$umethod) = explode('::',$class_method);
				try{
					$ref = new \ReflectionMethod($uclass,$umethod);
					$use_method_src = self::method_src($ref);
					$use_method_doc = self::trim_doc($ref->getDocComment());
					
					if(preg_match_all("/@throws\s+([^\s]+)(.*)/",$use_method_doc,$m)){
						foreach($m[1] as $k => $n){
							$throws[$n] = [$n,$m[2][$k]];
						}
					}
				}catch(\ReflectionException $e){
				}
			}
			foreach($throws as $n => $t){				
				try{
					$ref = new \ReflectionClass($n);
					$doc = empty($t[1]) ? trim(preg_replace('/@.+/','',self::trim_doc($ref->getDocComment()))) : $t[1];				
					$throw_param[$n] = new \ebi\man\DocParam(
						$ref->getName(),
						$ref->getName(),
						$doc
					);
				}catch(\ReflectionException $e){
				}
			}
			$info->set_opt('throws',$throw_param);
			
			return $info;
		}
		throw new \ebi\exception\NotFoundException();
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
