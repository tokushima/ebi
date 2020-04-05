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
		$info->document(trim(preg_replace('/\n*@.+/','',PHP_EOL.$document)));
		
		$info->set_opt('filename',$r->getFileName());
		$info->set_opt('extends',(($r->getParentClass() === false) ? null : $r->getParentClass()->getName()));
		$info->set_opt('abstract',$r->isAbstract());
		$info->set_opt('see_list',self::find_see($document));
		
		self::find_merge_deprecate($info,$document);
		
		$m = [];
		$methods = $static_methods = [];
		foreach($r->getMethods() as $method){
			if(substr($method->getName(),0,1) != '_' && $method->isPublic()){
				$ignore = ['getIterator'];
				
				if(!in_array($method->getName(),$ignore)){
					$bool = true;
					
					foreach([\ebi\Obj::class,\ebi\Dao::class,\ebi\flow\Request::class,\ebi\Request::class] as $ignore_class){
						if($r->getName() != $ignore_class && $method->getDeclaringClass()->getName() == $ignore_class){
							$bool = false;
						}
					}
					if($bool){
						$method_info = new \ebi\Dt\DocInfo();
						$method_info->name($method->getName());
						
						$method_document = self::get_method_document($method);
						
						if(preg_match("/@plugin\s+([^\s]+)/",$method_document,$m)){
							$method_info->set_opt('plugin_caller', trim($m[1]));
						}
						$method_document = self::find_merge_deprecate($method_info,$method_document);
						
						list($summary) = explode(PHP_EOL,trim(preg_replace('/@.+/','',$method_document)));
						
						$method_info->document($summary);
						
						if($method->isStatic()){
							$static_methods[] = $method_info;
						}else{
							$methods[] = $method_info;
						}
					}
				}
			}
		}
		$info->set_opt('static_methods',$static_methods);
		$info->set_opt('methods',$methods);
		
		$properties = [];
		$anon = \ebi\Annotation::get_class($info->name(),'var','summary');
		$is_obj = $r->isSubclassOf(\ebi\Obj::class);
		
		$get_type_format = function($arr){
			if(isset($arr['type'])){
				if(isset($arr['attr'])){
					if($arr['attr'] == 'a'){
						return $arr['type'].'[]';
					}else if($arr['attr'] == 'h'){
						return $arr['type'].'{}';
					}
				}
				return $arr['type'];
			}else{
				return 'mixed';
			}
		};
		foreach($r->getProperties() as $prop){
			if($prop->isPublic() || ($is_obj && $prop->isProtected())){
				$name = $prop->getName();
				
				if($name[0] != '_' && !$prop->isStatic()){
					$properties[$name] = new \ebi\Dt\DocParam(
						$name,
						$get_type_format(isset($anon[$name]) ? $anon[$name] : 'mixed')
					);
					$properties[$name]->summary(
						self::find_merge_deprecate(
							$properties[$name],
							(isset($anon[$name]['summary']) ? $anon[$name]['summary'] : null),
							$info,
							true
						)
					);
					$properties[$name]->set_opt(
						'hash',
						($prop->isPublic() || !(isset($anon[$name]['hash']) && $anon[$name]['hash'] === false))
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
					if(!isset($call_plugins[$v[0]]) || empty(trim($call_plugins[$v[0]]->document()))){
						$call_plugins[$v[0]] = \ebi\Dt\DocInfo::parse($v[0], $src, $m[0][$k][1]);
						$call_plugins[$v[0]]->set_opt('added',[]);
					}
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
			ksort($call_plugins);
		}
		$info->set_opt('call_plugins',$call_plugins);
		
		return $info;
	}
	
	private static function get_class_name($class_name){
		$class_name = str_replace(['.','/'],['\\','\\'],$class_name);
		
		if(class_exists($class_name)){
			$r = new \ReflectionClass($class_name);
			$name = $r->getName();
			
			if(substr($name,0,1) !== '\\'){
				$name = '\\'.$name;
			}
			return $name;
		}
		return false;
	}
	
	private static function find_merge_params($info,$parameters){
		$doc_params = $info->params();
		$info->rm_params();
		
		foreach($parameters as $param){
			$has = false;
			
			foreach($doc_params as $doc_param){
				if($doc_param->name() == $param->getName()){
					$info->add_params($doc_param);
					$has = true;
					break;
				}
			}
			if(!$has){
				$info->add_params(new \ebi\Dt\DocParam($param->getName(),'mixed'));
			}
		}
	}
	private static function find_merge_deprecate($info,$summary,$rootobj=null,$containt=false){
		$m = $mm = [];
		
		if(preg_match('/'.($containt ? '' : '^').'@deprecated(.*)/m',$summary,$m)){
			$d = time();
			
			if(preg_match('/\d{4}[\-\/\.]*\d{1,2}[\-\/\.]*\d{1,2}/',$m[1],$mm)){
				$d = strtotime($mm[0]);
			}
			$info->set_opt('deprecated',$d);
			
			if(isset($rootobj)){
				if(empty($rootobj->opt('first_depricated_date')) || $rootobj->opt('first_depricated_date') > $d){
					$rootobj->set_opt('first_depricated_date',$d);
				}
			}
		}
		return trim(preg_replace('/@.+/','',$summary));
	}
	private static function find_merge_request_context($info,$document){
		$requests = \ebi\Dt\DocParam::parse('request',$document);
		$contexts = \ebi\Dt\DocParam::parse('context',$document);
		
		foreach([$requests,$contexts] as $v){
			foreach($v as $r){
				$r->summary(self::find_merge_deprecate($r,$r->summary(),$info,true));
			}
		}
		$info->set_opt('requests',$requests);
		$info->set_opt('contexts',$contexts);
		
		if(!$info->is_return() && $info->has_opt('contexts')){
			$info->return(new \ebi\Dt\DocParam('return','mixed{}'));
		}
	}
	
	private static function find_throws($throws,$doc,$src){
		$m = [];
		
		if(preg_match_all("/@throws\s+([^\s]+)(.*)/",$doc,$m)){
			foreach($m[1] as $k => $n){
				if(false !== ($class_name = self::get_class_name($n))){
					if(isset($throws[$class_name])){
						$throws[$class_name] = [$class_name,$throws[$class_name][1].trim(PHP_EOL.$m[2][$k])];
					}else{
						$throws[$class_name] = [$class_name,$m[2][$k]];
					}
				}
			}
		}
		if(preg_match_all("/throw new\s([\w\\\\]+)/",$src,$m)){
			foreach($m[1] as $k => $n){
				if(false !== ($class_name = self::get_class_name($n))){
					if(!isset($throws[$class_name])){
						$throws[$n] = [$n,''];
					}
				}
			}
		}
		if(preg_match_all("/catch\s*\(\s*([\w\\\\]+)/",$src,$m)){
			foreach($m[1] as $k => $n){
				if(false !== ($class_name = self::get_class_name($n))){
					if(array_key_exists($class_name,$throws)){
						unset($throws[$class_name]);
					}
				}
			}
		}
		return $throws;
	}
	
	private static function merge_find_throws(array $throws){
		$throw_param = [];
		
		foreach($throws as $n => $t){
			try{
				$ref = new \ReflectionClass($n);
				$doc = empty($t[1]) ? trim(preg_replace('/@.+/','',self::trim_doc($ref->getDocComment()))) : $t[1];
				
				$throw_param[$n] = new \ebi\Dt\DocParam(
					$ref->getName(),
					$ref->getName(),
					trim($doc)
				);
			}catch(\ReflectionException $e){
			}
		}
		return $throw_param;
	}
	
	public static function find_mail_doc($mail_info,$src){
		$m = [];
		
		if(preg_match_all('/[^\w\/_]'.preg_quote($mail_info->name(),'/').'/',$src,$m,PREG_OFFSET_CAPTURE)){
			$doc = \ebi\Dt\DocInfo::parse('',$src,$m[0][0][1]);
			
			if(empty($doc->document())){
				if(preg_match('/\/\*\*(((?!\/\*\*).)*@real\s'.preg_quote($mail_info->name(),'/').'((?!\/\*\*).)*?\*\/)/s',$src,$m)){
					$doc = \ebi\Dt\DocInfo::parse('',$m[1]);
				}
			}
			$mail_info->set_opt('use',true);
			$mail_info->set_opt('description',$doc->document());
			
			foreach($doc->params() as $p){
				$mail_info->add_params($p);
			}
			
			$mail_info->add_params(new \ebi\Dt\DocParam('t', '\ebi\FlowHelper','Helper'));
			$mail_info->add_params(new \ebi\Dt\DocParam('xtc', 'string{}','Template Code'));
			
			$mail_src = \ebi\Util::file_read(self::mail_template_path($mail_info->name()));
			$varnames = [];
			
			if(preg_match_all('/\$([\w_]+)/',$mail_src,$m)){
				$varnames = array_unique($m[1]);
				
				if(preg_match_all('/[ :](var=|counter=|key=)["\']([\w_]+)["\']/',$mail_src,$m)){
					foreach($m[2] as $rtvar){
						foreach($varnames as $k => $v){
							if($v === $rtvar){
								unset($varnames[$k]);
								break;
							}
						}
					}
				}
				foreach($varnames as $k => $varname){
					foreach($mail_info->params() as $param){
						if($varname === $param->name()){
							unset($varnames[$k]);
						}
					}
				}
			}
			$mail_info->set_opt('undefined_vars',$varnames);
			
			return true;
		}
		return false;
	}
	private static function find_see($document){
		$see = $m = [];
		
		if(preg_match_all("/@see\s+([\w\.\:\\\\]+.+)/",$document,$m)){
			foreach($m[1] as $v){
				$v = trim($v);
				
				if(strpos($v,'://') !== false){
					$see[$v] = ['type'=>'url','url'=>$v];
				}else if(strpos($v,'::') !== false){
					list($see_class,$see_method) = explode('::',$v,2);
					$see[$v] = ['type'=>'method','class'=>$see_class,'method'=>$see_method];
				}else if(substr($v,-1) != ':'){
					$see[$v] = ['type'=>'class','class'=>$v];
				}
			}
		}
		return $see;
	}
	
	/**
	 * クラスドメソッドのキュメント
	 * @param string $class
	 * @param string $method
	 * 
	 * @throws \ebi\exception\NotFoundException
	 * @return \ebi\Dt\DocInfo
	 */
	public static function method_info($class,$method,$detail=false,$deep=false){
		$ref = new \ReflectionMethod(self::get_class_name($class),$method);
		$is_request_flow = $ref->getDeclaringClass()->isSubclassOf(\ebi\flow\Request::class);
		$method_fullname = $ref->getDeclaringClass()->getName().'::'.$ref->getName();
		
		if(is_file($ref->getDeclaringClass()->getFileName())){
			$document = '';
			
			if($is_request_flow){
				try{
					$before_ref = new \ReflectionMethod(self::get_class_name($class),'__before__');
					
					$m = [];
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
			
			self::find_merge_deprecate($info,$document);
			self::find_merge_params($info,$ref->getParameters());
			self::find_merge_request_context($info, $document);
			
			$info->set_opt('class',self::get_class_name($class));
			$info->set_opt('method',$ref->getName());
			$info->set_opt('see_list',self::find_see($document));
			
			if(preg_match("/@plugin\s+([^\s]+)/",$document,$m)){
				$info->set_opt('plugin_caller',trim($m[1]));
			}
			if(!$info->is_version()){
				$info->version(date('Ymd',filemtime($ref->getDeclaringClass()->getFileName())));
			}
			
			$m = [];
			if(preg_match("/@http_method\s+([^\s]+)/",$document,$m)){
				$info->set_opt('http_method',strtoupper(trim($m[1])));
			}else{
				$info->set_opt('http_method',(
					(strpos($src,'$this->is_post()') !== false) &&
					(strpos($src,'!$this->is_post()') === false)
				) ? ' POST' : null);
			}
			
			$call_plugins = $plugins = [];
			$throws = $mail_list = [];
			
			if($detail){
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
				$class_plugins = $class_info->opt('call_plugins');
				
				foreach(array_keys($plugins) as $plugin_method_name){
					if(array_key_exists($plugin_method_name, $class_plugins)){
						$call_plugins[$class_plugins[$plugin_method_name]->opt('class').'::'.$plugin_method_name] = $class_plugins[$plugin_method_name];
					}
				}
			}
			$info->set_opt('call_plugins',$call_plugins);
			
			if($deep){
				$use_method_list = self::use_method_list($ref->getDeclaringClass()->getName(),$ref->getName());
				$use_method_list = array_merge($use_method_list,[$method_fullname]);
				
				foreach($call_plugins as $plugin_info){
					foreach($plugin_info->opt('added') as $class_name){
						$use_method_list[] = $class_name.'::'.$plugin_info->name();
					}
				}
			}else{
				$use_method_list = [$method_fullname];
			}
			$use_method_list = array_unique($use_method_list);
			
			foreach(array_keys($use_method_list) as $k){
				$use_method_list[$k] = '\\'.$use_method_list[$k];
				$use_method_list[$k] = preg_replace('/\\\\+/','\\',$use_method_list[$k]);
			}
			krsort($use_method_list);
			$use_method_list = array_unique($use_method_list);
			
			if($detail){
				$mail_template_list = self::mail_template_list();
				
				foreach($use_method_list as $class_method){
					list($uclass,$umethod) = explode('::',$class_method);
					
					try{
						$ref = new \ReflectionMethod($uclass,$umethod);
						$use_method_src = self::method_src($ref);
						$use_method_doc = self::trim_doc($ref->getDocComment());
						
						$throws = self::find_throws($throws,$use_method_doc,$use_method_src);
						
						
						foreach($mail_template_list as $mail_info){
							if(self::find_mail_doc($mail_info, $use_method_src)){
								$mail_list[$mail_info->opt('x_t_code')] = $mail_info;
							}
						}
					}catch(\ReflectionException $e){
					}
				}
			}
			$info->set_opt('mail_list',$mail_list);
			$info->set_opt('throws',self::merge_find_throws($throws));
			
			return $info;
		}
		throw new \ebi\exception\NotFoundException();
	}
	
	/**
	 * クロージャのキュメント
	 * @param \Closure $closure
	 * @return \ebi\Dt\DocInfo
	 */
	public static function closure_info(\Closure $closure){
		$ref = new \ReflectionFunction($closure);
		$doc = self::trim_doc($ref->getDocComment());
		$src = implode(array_slice(file($ref->getFileName()),$ref->getStartLine(),($ref->getEndLine()-$ref->getStartLine()-1)));
		
		$info = \ebi\Dt\DocInfo::parse(null,$doc);
		self::find_merge_params($info,$ref->getParameters());
		self::find_merge_deprecate($info,$doc);
		self::find_merge_request_context($info, $doc);
		
		$mail_list = [];
		foreach(self::mail_template_list() as $mail_info){
			if(self::find_mail_doc($mail_info, $src)){
				$mail_list[$mail_info->opt('x_t_code')] = $mail_info;
			}
		}
		$info->set_opt('mail_list',$mail_list);
		$info->set_opt('throws',self::merge_find_throws(self::find_throws([],$doc,$src)));
		$info->set_opt('see_list',self::find_see($doc));
		
		return $info;
	}
	
	/**
	 * メールテンプレートパス
	 * @param string $path
	 * @return string
	 */
	public static function mail_template_path($path){
		$dir = \ebi\Conf::get(\ebi\Mail::class.'@resource_path',\ebi\Conf::resource_path('mail'));
		return \ebi\Util::path_absolute($dir, $path);
	}
	
	/**
	 * メールテンプレート一覧
	 * @return \ebi\Dt\DocInfo[]
	 */
	public static function mail_template_list(){
		$path = self::mail_template_path('');
		$template_list = [];
		
		try{
			foreach(\ebi\Util::ls($path,true,'/\.xml$/') as $f){
				$info = new \ebi\Dt\DocInfo();
				$info->name(str_replace(\ebi\Util::path_slash($path,null,true),'',$f->getPathname()));
				
				try{
					$xml = \ebi\Xml::extract(file_get_contents($f->getPathname()),'mail');
					
					try{
						// signatureは無視
						$xml->find_get('signature');
					}catch(\ebi\exception\NotFoundException $e){
						$info->version($xml->in_attr('version',date('Ymd',filemtime($f->getPathname()))));
						$info->set_opt('x_t_code',\ebi\Mail::xtc($info->name()));
						
						try{
							$subject = trim($xml->find_get('subject')->value());
							$info->document($subject);
							$info->set_opt('subject',$subject);
						}catch(\ebi\exception\NotFoundException $e){
						}
						try{
							$summary = trim($xml->find_get('summary')->value());
							$info->document($summary);
						}catch(\ebi\exception\NotFoundException $e){
						}
						$template_list[] = $info;
					}
				}catch(\ebi\exception\NotFoundException $e){
				}
			}
		}catch(\ebi\exception\InvalidArgumentException $e){
		}
		return $template_list;
	}
	
	public static function method_src(\ReflectionMethod $ref){
		if(is_file($ref->getDeclaringClass()->getFileName())){
			return implode(array_slice(file($ref->getDeclaringClass()->getFileName()),$ref->getStartLine(),($ref->getEndLine()-$ref->getStartLine()-1)));
		}
		return '';
	}
	public static function trim_doc($doc){
		return trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(['/'.'**','*'.'/'],'',$doc)));
	}
	private static function use_method_list($class,$method,&$loaded_method_src=[]){
		$list = [];
		
		try{
			$ref = new \ReflectionMethod($class,$method);
			$kname = $ref->getDeclaringClass()->getName().'::'.$ref->getName();
			
			if(isset($loaded_method_src[$kname])){
				return [];
			}
			$loaded_method_src[$kname] = true;
			$list[$kname] = true;
			
			if(is_file($ref->getDeclaringClass()->getFileName())){
				$src = self::method_src($ref);
				$vars = ['$this'=>$class];
				$m = [];
				
				foreach($ref->getParameters() as $param){
					if($param->hasType()){
						$type_class = ($param->getType() instanceof \ReflectionNamedType) ? $param->getType()->getName() : null;
						
						if(!empty($type_class) && class_exists($type_class)){
							$vars['$'.$param->getName()] = $type_class;
						}
					}
				}
				if(preg_match_all('/(\$\w+)\s*=\s*new\s+([\\\\\w]+)/',$src,$m)){
					foreach($m[1] as $k => $v){
						$vars[$v] = $m[2][$k];
					}
				}
				if(preg_match_all('/(\$\w+)\s*=\s*([\\\\\w]+)::(\w+)/',$src,$m)){
					$r = [];
					
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
					list($c,$m) = explode('::',$mcm);
					$c = str_replace('\\\\','\\',($c[0] !== '\\') ? '\\'.$c : $c);
					
					if(!isset($loaded_method_src[$c.'::'.$m])){
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