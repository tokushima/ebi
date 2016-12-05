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
		$info = new \ebi\Dt\DocInfo();
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
					
					$method_info = new \ebi\Dt\DocInfo();
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
					$properties[$name] = new \ebi\Dt\DocParam(
						$name,
						(isset($anon[$name]['type']) ? $anon[$name]['type'] : 'mixed'),
						(isset($anon[$name]['summary']) ? $anon[$name]['summary'] : null),
						['hash'=>($prop->isPublic() || !(isset($anon[$name]['hash']) && $anon[$name]['hash'] === false))]
					);
				}
			}
		}
		$info->set_opt('properties',$properties);

		
		$config_list = [];		
		foreach([
			"/Conf::gets\(([\"\'])(.+?)\\1/"=>'mixed[]',
			"/Conf::get\(([\"\'])(.+?)\\1/"=>'mixed',
			"/self::get_self_conf_get\(([\"\'])(.+?)\\1/"=>'mixed',
		] as $preg => $default_type){
			if(preg_match_all($preg,$src,$m,PREG_OFFSET_CAPTURE)){
				foreach($m[2] as $k => $v){
					// 呼び出しが重複したら先にドキュメントがあった方
					if(!array_key_exists($v[0],$config_list) || !$config_list[$v[0]]->has_params()){
						$conf_info = \ebi\Dt\DocInfo::parse($v[0],$src,$m[0][$k][1]);
						$conf_info->set_opt('def',\ebi\Conf::exists($r->getName(),$v[0]));
		
						if(!$conf_info->has_params()){
							$conf_info->add_params(new \ebi\Dt\DocParam('val',$default_type));
						}
						$config_list[$v[0]] = $conf_info;
					}
				}
			}
		}
		ksort($config_list);
		$info->set_opt('config_list',$config_list);
		
		
		$call_plugins = [];
		foreach([
			"/->get_object_plugin_funcs\(([\"\'])(.+?)\\1/",
			"/->call_object_plugin_funcs\(([\"\'])(.+?)\\1/",
			"/::call_class_plugin_funcs\(([\"\'])(.+?)\\1/",
			"/->call_object_plugin_func\(([\"\'])(.+?)\\1/",
			"/::call_class_plugin_func\(([\"\'])(.+?)\\1/",
		] as $preg){
			if(preg_match_all($preg,$src,$m,PREG_OFFSET_CAPTURE)){
				foreach($m[2] as $k => $v){
					$call_plugins[$v[0]] = \ebi\Dt\DocInfo::parse($v[0], $src, $m[0][$k][1]);
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
	public static function method_info($class,$method,$deep=true){
		$ref = new \ReflectionMethod(self::get_class_name($class),$method);
		$is_request_flow = $ref->getDeclaringClass()->isSubclassOf('\ebi\flow\Request');
		$method_fullname = $ref->getDeclaringClass()->getName().'::'.$ref->getName();
		
		if(is_file($ref->getDeclaringClass()->getFileName())){
			$document = '';
			
			if($is_request_flow){
				try{
					$before_ref = new \ReflectionMethod(self::get_class_name($class),'__before__');
					
					if(preg_match_all('/@.+$/',self::get_method_document($before_ref),$m)){
						foreach($m[0] as $a){
							$document = $a.PHP_EOL.$document;
						}
					}
				}catch(\ReflectionException $e){
				}
			}
			$document = $document.self::get_method_document($ref);
			$src = self::method_src($ref);

			$info = \ebi\Dt\DocInfo::parse($method_fullname,$document);
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
			$info->set_opt('requests',\ebi\Dt\DocParam::parse('request',$document));
			$info->set_opt('contexts',\ebi\Dt\DocParam::parse('context',$document));
			$info->set_opt('args',\ebi\Dt\DocParam::parse('arg',$document));

			if(!$info->is_return() && $info->has_opt('contexts')){
				$info->return(new \ebi\Dt\DocParam('return','mixed{}'));
			}
			
			if($deep){
				$call_plugins = [];
				$plugins = [];
				
				foreach([
					"/->get_object_plugin_funcs\(([\"\'])(.+?)\\1/",
					"/->call_object_plugin_funcs\(([\"\'])(.+?)\\1/",
					"/::call_class_plugin_funcs\(([\"\'])(.+?)\\1/",
					"/->call_object_plugin_func\(([\"\'])(.+?)\\1/",
					"/::call_class_plugin_func\(([\"\'])(.+?)\\1/",						
				] as $preg){
					if(preg_match_all($preg,$src,$m,PREG_OFFSET_CAPTURE)){
						foreach($m[2] as $k => $v){
							$plugins[$v[0]] = true;
						}
					}
				}
				$class_info = self::class_info($ref->getDeclaringClass()->getName());
				$class_plugins = $class_info->opt('plugins');
			
				foreach(array_keys($plugins) as $plugin_method_name){
					if(array_key_exists($plugin_method_name, $class_plugins)){
						$call_plugins[$class_plugins[$plugin_method_name]->opt('class').'::'.$plugin_method_name] = $class_plugins[$plugin_method_name];
					}
				}
				$info->set_opt('plugins',$call_plugins);
	
				
				$use_method_list = self::use_method_list($ref->getDeclaringClass()->getName(),$ref->getName());
				$use_method_list = array_merge($use_method_list,[$method_fullname]);
				
				foreach($call_plugins as $plugin_info){
					foreach($plugin_info->opt('added') as $class_name){
						$use_method_list[] = $class_name.'::'.$plugin_info->name();
					}
				}
				
				$mail_template_list = self::mail_template_list();
				$throws = $throw_param = $mail_list = [];
				
				foreach($use_method_list as $class_method){
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
						
						foreach($mail_template_list as $k => $mail_info){
							if(preg_match('/[^\w\/]'.preg_quote($mail_info->name(),'/').'/',$use_method_src)){
								$mail_template_list[$k]->set_opt('use',true);
							}
						}
					}catch(\ReflectionException $e){
					}
				}
				
				foreach($throws as $n => $t){				
					try{
						$ref = new \ReflectionClass($n);
						$doc = empty($t[1]) ? trim(preg_replace('/@.+/','',self::trim_doc($ref->getDocComment()))) : $t[1];				
						$throw_param[$n] = new \ebi\Dt\DocParam(
							$ref->getName(),
							$ref->getName(),
							$doc
						);
					}catch(\ReflectionException $e){
					}
				}
				$info->set_opt('throws',$throw_param);
				
				foreach($mail_template_list as $mail_info){
					if($mail_info->opt('use') === true){
						$mail_list[] = $mail_info;
					}
				}
				$info->set_opt('mail_list',$mail_list);
			}			
			return $info;
		}
		throw new \ebi\exception\NotFoundException();
	}
	public static function mail_template_list(){
		$path = \ebi\Conf::get(\ebi\Mail::class.'@resource_path',\ebi\Conf::resource_path('mail'));
		$template_list = [];
	
		try{
			foreach(\ebi\Util::ls($path,true,'/\.xml$/') as $f){
				$info = new \ebi\Dt\DocInfo();
				$info->name(str_replace(\ebi\Util::path_slash($path,null,true),'',$f->getPathname()));
	
				try{
					$xml = \ebi\Xml::extract(file_get_contents($f->getPathname()),'mail');
					$info->document($xml->find_get('subject')->value());
	
					$info->set_opt('x_t_code',\ebi\Mail::xtc($info->name()));
					$template_list[] = $info;
				}catch(\ebi\exception\NotFoundException $e){
				}
			}
		}catch(\ebi\exception\InvalidArgumentException $e){
		}
		return $template_list;
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
