<?php
namespace ebi;
/**
 * リクエスト処理ワークフロー
 * @author tokushima
 *
 */
class Flow{
	use \ebi\Plugin;

	private static $app_url;
	private static $media_url;
	
	private static $template_path;
	private static $apps_path;
	private static $package_media_url = 'package/resources/media';
	
	private static $url_pattern = [];
	private static $selected_class_pattern = [];
	
	private static $is_get_branch = false;
	private static $branch_map = [];
	
	private static $is_get_map = false;
	private static $map = [];
	
	private static $template;
	
	public static function app_url(){
		return self::$app_url;
	}
	public static function media_url(){
		return self::$media_url;
	}
	public static function url_pattern(){
		return self::$url_pattern;
	}
	public static function selected_class_pattern(){
		return self::$selected_class_pattern;
	}
	/**
	 * mapsを取得する
	 * @param string $file
	 * @return array
	 */
	public static function get_map($file=null){
		if(!empty($file)){
			self::$is_get_map = true;

			ob_start();
				include($file);
			ob_end_clean();
		}
		return self::$map;
	}
	/**
	 * アプリケーションの実行
	 * @param array $map
	 */
	public static function app($map){
		if(self::$is_get_branch){
			self::$branch_map = $map;
		}else{
			 self::execute($map);
		}
	}
	
	private static function template(array $vars,$selected_pattern,$ins,$path,$media=null){
		self::$template->set_object_plugin(new \ebi\FlowInvalid());
		self::$template->set_object_plugin(new \ebi\Paginator());
		self::$template->media_url(empty($media) ? self::$media_url : $media);
		self::$template->cp($vars);
		self::$template->vars('t',new \ebi\FlowHelper((isset($selected_pattern['name']) ? $selected_pattern['name'] :  null),$ins));
		$src = self::$template->read($path);
		
		print($src);
		self::terminate();
		exit;
	}
	/**
	 * pattern名でリダイレクトする
	 * ://がある場合はURLとみなす
	 * map_nameが連想配列の場合は、$varsにmap_nameのキーが含まれていた場合にのみリダイレクトする
	 *  ext.. [['exec'=>'ptn1','confirm'=>'ptn2'],['confirm'=>true]]
	 * map_nameが配列の場合は最初の値をmap_nameとし残りをpatternに渡す値としてvarsからとる
	 *  ext.. [['ptn1','var1','var2'],['var2'=>123,'var1'=>456]]
	 * 
	 * @param string $map_name
	 * @param  array $vars
	 * @param array $pattern
	 * @throws \InvalidArgumentException
	 */
	private static function map_redirect($map_name,$vars=[],$pattern=[]){
		self::terminate();
		
		if(is_array($map_name) && !isset($map_name[0])){
			$bool = false;
			foreach($map_name as $k => $a){
				if(array_key_exists($k,$vars)){
					$map_name = $a;
					$bool = true;
					break;
				}
			}
 			if(!$bool){
 				return;
 			}
		}
		$name = is_string($map_name) ? $map_name : (is_array($map_name) ? array_shift($map_name) : null);
		$var_names = (!empty($map_name) && is_array($map_name)) ? $map_name : [];
		$args = [];
		
		if(empty($name)){
			\ebi\HttpHeader::redirect_referer();
		}
		foreach($var_names as $n){
			if(!isset($vars[$n])){
				throw new \ebi\exception\InvalidArgumentException('variable '.$n.' not found');
			}
			$args[$n] = $vars[$n];
		}
		if(strpos($name,'://') === false){
			if(isset($pattern['@'])){
				if(isset(self::$selected_class_pattern[$name][sizeof($args)])){
					$name = self::$selected_class_pattern[$name][sizeof($args)]['name'];
				}
			}else if(isset($pattern['branch'])){
				$name = $pattern['branch'].'#'.$name;
			}
		}
		if(strpos($name,'://') !== false){
			\ebi\HttpHeader::redirect($name);
		}
		if(isset(self::$url_pattern[$name][sizeof($args)])){
			$format = self::$url_pattern[$name][sizeof($args)];
			\ebi\HttpHeader::redirect(empty($args) ? $format : vsprintf($format,$args));
		}
		throw new \InvalidArgumentException('map `'.$name.'` not found');
	}
	private static function execute($map){
		if(is_array($map) && !isset($map['patterns'])){
			$map = ['patterns'=>$map];
		}else if(is_string($map)){
			$map = ['patterns'=>[''=>['action'=>$map]]];
		}else if(!isset($map['patterns']) || !is_array($map['patterns'])){
			throw new \InvalidArgumentException('pattern not found');
		}
		/**
		 * アプリケーションのベースURL
		 */
		self::$app_url = \ebi\Conf::get('app_url');
		/**
		 * メディアファイルのベースURL
		 */
		self::$media_url = \ebi\Conf::get('media_url');
		/**
		 * apps(action appのファイル群)のディレクトリパス
		 */
		self::$apps_path = \ebi\Util::path_slash(\ebi\Conf::get('apps_path',getcwd().'/apps/'),null,true);
		
		if(empty(self::$app_url)){
			$entry_file = null;
			foreach(debug_backtrace(false) as $d){
				if($d['file'] !== __FILE__){
					$entry_file = str_replace("\\",'/',$d['file']);
					break;
				}
			}
			self::$app_url = 'http://localhost:8000/'.basename($entry_file);
		}else if(substr(self::$app_url,-1) == '*'){
			$entry_file = null;
			foreach(debug_backtrace(false) as $d){
				if($d['file'] !== __FILE__){
					$entry_file = str_replace("\\",'/',$d['file']);
					break;
				}
			}
			self::$app_url = substr(self::$app_url,0,-1).basename($entry_file);
		}	
		self::$app_url = \ebi\Util::path_slash(str_replace('https://','http://',self::$app_url),null,true);

		if(empty(self::$media_url)){
			$media_path = preg_replace('/\/[^\/]+\.php[\/]$/','/',self::$app_url);
			self::$media_url = $media_path.'resources/media/';
		}
		self::$media_url = \ebi\Util::path_slash(self::$media_url,null,true);
		
		/**
		 * テンプレートのパス
		 */
		self::$template_path = \ebi\Util::path_slash(\ebi\Conf::get('template_path',\ebi\Conf::resource_path('templates')),null,true);
		self::$template = new \ebi\Template();
		
		self::$map = self::read($map);
		if(self::$is_get_map){
			self::$is_get_map = false;
			return;
		}
		$result_vars = [];
		$pathinfo = preg_replace("/(.*?)\?.*/","\\1",(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : ''));
		
		if(preg_match('/^\/'.preg_quote(self::$package_media_url,'/').'\/(\d+)\/(.+)$/',$pathinfo,$m)){			
			foreach(self::$map['patterns'] as $p){
				if((int)$p['pattern_id'] === (int)$m[1] && isset($p['@']) && is_file($file=($p['@'].'/resources/media/'.$m[2]))){
					\ebi\HttpFile::attach($file);
				}
			}
			\ebi\HttpHeader::send_status(404);
			exit;
		}
		
		foreach(self::$map['patterns'] as $k => $pattern){
			if(preg_match('/^'.(empty($k) ? '' : '\/').str_replace(['\/','/','@#S'],['@#S','\/','\/'],$k).'[\/]{0,1}$/',$pathinfo,$param_arr)){
				if(!empty($pattern['mode'])){
					/**
					 * Flowの実行モード
					 */
					$mode = \ebi\Conf::get('mode',\ebi\Conf::appmode());
					if(!in_array($mode,explode(',',$pattern['mode']))) {
						break;
					}
				}
				/**
				 * URLをhttpsにするか
				 */
				if($pattern['secure'] === true && \ebi\Conf::get('secure',true) !== false){
					if(substr(\ebi\Request::current_url(),0,5) === 'http:' &&
						(
							!isset($_SERVER['HTTP_X_FORWARDED_HOST']) ||
							(isset($_SERVER['HTTP_X_FORWARDED_PORT']) && $_SERVER['HTTP_X_FORWARDED_PORT'] != 443) ||
							(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https')
						)
					){
						header('Location: '.preg_replace('/^.+(:\/\/.+)$/','https\\1',\ebi\Request::current_url()));
						exit;
					}
					self::$media_url = str_replace('http://','https://',self::$media_url);
				}
				try{
					$funcs = $class = $method = $template = $ins = null;
					$exception = null;
					$has_flow_plugin = false;
					$result_vars = $plugins = [];
					array_shift($param_arr);
					
					if(!empty($pattern['action'])){
						if(is_string($pattern['action'])){
							list($class,$method) = explode('::',$pattern['action']);							
						}else if(is_callable($pattern['action'])){
							$funcs = $pattern['action'];
						}else{
							throw new \InvalidArgumentException($pattern['name'].' action invalid');
						}
					}
					foreach(self::$map['patterns'] as $m){
						self::$url_pattern[$m['name']][$m['num']] = $m['format'];
						
						if(!empty($class) && isset($pattern['@']) && isset($m['@']) && $pattern['pattern_id'] == $m['pattern_id']){
							self::$selected_class_pattern[substr($m['action'],strlen($class.'::'))][$m['num']] = ['format'=>$m['format'],'name'=>$m['name']];
						}
					}
					if(isset($pattern['redirect'])){
						self::map_redirect($pattern['redirect'],[],$pattern);
					}
					foreach(array_merge(self::$map['plugins'],$pattern['plugins']) as $m){
						$o = self::to_instance($m);
						self::set_class_plugin($o);
						$plugins[] = $o;
					}
					if(!isset($funcs) && isset($class)){
						$ins = self::to_instance($class);
						$ins_r = new \ReflectionClass($ins);
						$traits = [];
						
						while(true){
							$traits = array_merge($traits,$ins_r->getTraitNames());
							if(($ins_r = $ins_r->getParentClass()) === false){
								break;
							}
						}
						if($has_flow_plugin = in_array('ebi\\FlowPlugin',$traits)){
							foreach($ins->get_flow_plugins() as $m){
								$o = self::to_instance($m);
								$plugins[] = $o;
								self::set_class_plugin($o);
							}
							$ins->set_pattern($pattern);
						}
						if(in_array('ebi\\Plugin',$traits)){
							foreach($plugins as $o){
								$ins->set_object_plugin($o);
							}
						}
						$funcs = [$ins,$method];
					}
					foreach($plugins as $o){
						self::$template->set_object_plugin($o);
					}
					if(self::has_class_plugin('before_flow_action')){
						self::call_class_plugin_funcs('before_flow_action');
					}
					if($has_flow_plugin){
						$ins->before();
						$before_redirect = $ins->get_before_redirect();
						if(isset($before_redirect)){
							self::map_redirect($before_redirect,[],$pattern);
						}
					}
					if(isset($funcs)){
						try{
							$result_vars = call_user_func_array($funcs,$param_arr);
							
							if(!is_array($result_vars)){
								$result_vars = [];
							}
						}catch(\Exception $exception){
						}
					}
					if($has_flow_plugin){
						$ins->after();
						
						$template_block = $ins->get_template_block();
						if(!empty($template_block)){
							self::$template->put_block(\ebi\Util::path_absolute(self::$template_path,$ins->get_template_block()));
						}
						$template = $ins->get_template();
						$result_vars = array_merge($result_vars,$ins->get_after_vars());							
						$after_redirect = $ins->get_after_redirect();
						if(isset($after_redirect) && !isset($pattern['after'])){
							$pattern['after'] = $after_redirect;
						}
					}
					
					if(self::has_class_plugin('after_flow_action')){
						$result_vars = array_merge($result_vars,self::call_class_plugin_funcs('after_flow_action'));
					}
					if(isset($exception)){
						throw $exception;
					}
					\ebi\Exceptions::throw_over();
					
					if(isset($pattern['vars']) && is_array($pattern['vars'])){
						$result_vars = array_merge($result_vars,$pattern['vars']);
					}
					if(isset($pattern['post_after']) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'){
						self::map_redirect($pattern['post_after'],$result_vars,$pattern);
					}
					if(isset($pattern['after'])){
						self::map_redirect($pattern['after'],$result_vars,$pattern);
					}
					if(isset($pattern['template'])){
						if(isset($pattern['template_super'])){
							self::$template->template_super(\ebi\Util::path_absolute(self::$template_path,$pattern['template_super']));
						}
						self::template($result_vars,$pattern,$ins,\ebi\Util::path_absolute(self::$template_path,$pattern['template']));
					}else if(isset($template)){
						self::template($result_vars,$pattern,$ins,\ebi\Util::path_absolute(self::$template_path,$template));
					}else if(
						isset($pattern['@'])
						&& is_file($t=($pattern['@'].'/resources/templates/'.preg_replace('/^.+::/','',$pattern['action']).'.html'))
					){
						self::template($result_vars,$pattern,$ins,$t,self::$app_url.self::$package_media_url.'/'.$pattern['pattern_id']);
					}else if(
						self::$map['find_template'] === true
						&& is_file($t=\ebi\Util::path_absolute(self::$template_path,$pattern['name'].'.html'))
					){
						self::template($result_vars,$pattern,$ins,$t);
					}else if(self::has_class_plugin('flow_output')){
						self::call_class_plugin_funcs('flow_output',$result_vars);
						return self::terminate();
					}else{
						$to_array = function($value) use(&$to_array){
							switch(gettype($value)){
								case 'array':
									$list = [];
									foreach($value as $k => $v){
										$list[$k] = $to_array($v);
									}
									return $list;
								case 'object':
									$list = [];
									foreach((($value instanceof \Traversable) ? $value : get_object_vars($value)) as $k => $v){
										$list[$k] = $to_array($v);
									}
									return $list;
								default:
							}
							return $value;
						};
						\ebi\Log::disable_display();
							
						\ebi\HttpHeader::send('Content-Type','application/json');
						print(json_encode(['result'=>$to_array($result_vars)]));
						return self::terminate();
					}
				}catch(\Exception $e){
					\ebi\FlowInvalid::set($e);
					\ebi\Dao::rollback_all();
					\ebi\Log::warn($e);
					
					if(isset($pattern['error_status'])){
						\ebi\HttpHeader::send_status($pattern['error_status']);
					}else if(isset(self::$map['error_status'])){
						\ebi\HttpHeader::send_status(self::$map['error_status']);
					}					
					if(isset($pattern['vars']) && !empty($pattern['vars']) && is_array($pattern['vars'])){
						$result_vars = array_merge($result_vars,$pattern['vars']);
					}
					if(isset($pattern['@']) && is_file($t=$pattern['@'].'/resources/templates/error.html')){
						self::template($result_vars,$pattern,$ins,$t,self::$app_url.self::$package_media_url.'/'.$pattern['pattern_id']);
					}else if(isset($pattern['error_redirect'])){
						self::map_redirect($pattern['error_redirect'],[],$pattern);
					}else if(isset($pattern['error_template'])){
						self::template($result_vars,$pattern,$ins,\ebi\Util::path_absolute(self::$template_path,$pattern['error_template']));
					}else if(isset(self::$map['error_redirect'])){
						self::map_redirect(self::$map['error_redirect'],[],$pattern);
					}else if(isset(self::$map['error_template'])){
						self::template($result_vars,$pattern,$ins,\ebi\Util::path_absolute(self::$template_path,self::$map['error_template']));
					}else if(self::has_class_plugin('flow_exception')){
						self::call_class_plugin_funcs('flow_exception',$e);
						return self::terminate();
					}else if(self::has_class_plugin('flow_output')){
						self::call_class_plugin_funcs('flow_output',['error'=>['message'=>$e->getMessage()]]);
						return self::terminate();
					}
					$message = [];
					foreach(\ebi\FlowInvalid::get() as $g => $e){
						$message[] = ['message'=>$e->getMessage(),
										'group'=>$g,
										'type'=>basename(str_replace("\\",'/',get_class($e)))
										];
					}
					\ebi\Log::disable_display();
					
					\ebi\HttpHeader::send_status(500);
					\ebi\HttpHeader::send('Content-Type','application/json');
					print(json_encode(['error'=>$message]));
					
					return self::terminate();
				}
			}
		}
		if(isset(self::$map['nomatch_redirect'])){
			if(strpos(self::$map['nomatch_redirect'],'://') === false){
				foreach(self::$map['patterns'] as $m){
					if(self::$map['nomatch_redirect'] == $m['name']){
						self::$url_pattern[$m['name']][$m['num']] = $m['format'];
						break;
					}
				}
			}
			self::map_redirect(self::$map['nomatch_redirect'],[],[]);
		}
		\ebi\HttpHeader::send_status(404);
		return self::terminate();
	}
	private static function terminate(){
		\ebi\FlowInvalid::clear();
		return;
	}
	private static function fixed_vars($fixed_keys,$map,$exmap=[]){
		$result = [];
		foreach($fixed_keys as $t => $keys){
			foreach($keys as $k){
				if($t == 0){
					$result[$k] = isset($map[$k]) ? $map[$k] : (isset($exmap[$k]) ? $exmap[$k] : null);
				}else{
					$result[$k] = [];
					if(isset($map[$k])){
						$result[$k] = is_array($map[$k]) ? $map[$k] : [$map[$k]];
					}
					if(isset($exmap[$k])){
						$result[$k] = array_merge($result[$k],(is_array($exmap[$k]) ? $exmap[$k] : [$exmap[$k]]));
					}
				}
			}
		}
		return $result;
	}
	private static function fixed_automap($url,$class,$name){
		$result = [];
		try{
			$r = new \ReflectionClass(str_replace('.','\\',$class));
			$d = substr($r->getFilename(),0,-4);
	
			foreach($r->getMethods(\ReflectionMethod::IS_PUBLIC) as $m){
				if(!$m->isStatic() && substr($m->getName(),0,1) != '_'){
					if((boolean)preg_match('/@automap[\s]*/',$m->getDocComment())){
						$suffix = '';
						$auto_anon = preg_match('/@automap\s.*@(\[.*\])/',$m->getDocComment(),$a) ? \ebi\Annotation::activation($a[1]) : [];
						$base_name = $m->getName();
						if(!is_array($auto_anon)){
							throw new \ebi\exception\InvalidArgumentException($r->getName().'::'.$m->getName().' automap annotation error');
						}
						if(isset($auto_anon['suffix'])){
							$suffix = $auto_anon['suffix'];
							unset($auto_anon['suffix']);
						}
						if(isset($auto_anon['name'])){
							$base_name = $auto_anon['name'];
							unset($auto_anon['name']);
						}
						$murl = $url.(($m->getName() == 'index') ? '' : (($url == '') ? '' : '/').$base_name).str_repeat('/(.+)',$m->getNumberOfRequiredParameters());
							
						for($i=0;$i<=$m->getNumberOfParameters()-$m->getNumberOfRequiredParameters();$i++){
							$result[$murl.$suffix] = [
									'name'=>$name.'/'.$base_name
									,'action'=>$class.'::'.$m->getName()
									,'@'=>$d
							];
							if(!empty($auto_anon)){
								$result[$murl.$suffix] = array_merge($result[$murl.$suffix],$auto_anon);
							}
							$murl .= '/(.+)';
						}
					}
				}
			}
		}catch(\ReflectionException $e){
			throw new \InvalidArgumentException($class.' not found');
		}
		return $result;
	}
	private static function url_format_func($url,$map_secure,$conf_secure,$https,$http){
		$num = 0;
		$format = \ebi\Util::path_absolute(
				(($conf_secure && $map_secure === true) ? $https : $http),
				(empty($url)) ? '' : substr(preg_replace_callback("/([^\\\\])(\(.*?[^\\\\]\))/",function($n){return $n[1].'%s';},' '.$url,-1,$num),1)
		);
		return [str_replace(['\\\\','\\.','_ESC_'],['_ESC_','.','\\'],$format),$num];
	}
	private static function expand_branch($app_map){
		$map_pattern_keys = [
				0=>['name','action','redirect',
						'media_path',
						'template','template_path','template_super',
						'error_redirect','error_status','error_template',
						'suffix','secure','mode','after','post_after',
				]
				,1=>['plugins','args','vars']
		];
		$root_keys = [
				0=>['media_path',
						'nomatch_redirect',
						'template_path',
						'error_redirect','error_status','error_template',
						'secure','find_template',
				]
				,1=>['plugins','patterns']
		];		
		$patterns = [];
		if(is_file($f=self::$apps_path.$app_map['app'].'.php')){
			self::$is_get_branch = true;
			ob_start();
				$rtn = include($f);
			ob_end_clean();
			self::$is_get_branch = false;
			$branch_map = self::fixed_vars($root_keys,self::read(self::$branch_map),$app_map);

			foreach($branch_map['patterns'] as $bk => $bv){
				$bm = self::fixed_vars($map_pattern_keys,$bv,$branch_map);
				$bm['name'] = $app_map['name'].'#'.$bm['name'];
				$bm['pattern_id'] = $app_map['name'].'#'.$bv['pattern_id'];
				$bm['branch'] = $app_map['app'];

				if(isset($bv['@'])){
					$bm['@'] = $bv['@'];
				}
				$patterns[$app_map['url'].(empty($bk) ? '' : '/'.$bk)] = $bm;
			}
		}
		return $patterns;
	}
	private static function read($map){
		$map_pattern_keys = [
				0=>['name','action','redirect',
					'media_path',
					'template','template_path','template_super',
					'error_redirect','error_status','error_template',
					'suffix','secure','mode','after','post_after',
				]
				,1=>['plugins','args','vars']
		];
		$root_keys = [
				0=>['media_path',
					'nomatch_redirect',
					'template_path',
					'error_redirect','error_status','error_template',
					'secure','find_template',
				]
				,1=>['plugins','patterns']
		];
		
		$patterns = [];
		$map = self::fixed_vars($root_keys,$map);
		
		foreach($map['patterns'] as $k => $v){
			if(substr($k,0,1) == '/'){
				$k = substr($k,1);
			}
			if(substr($k,-1) == '/'){
				$k = substr($k,0,-1);			
			}
			if(is_callable($v)){
				$v = ['action'=>$v];
			}
						
			if(isset($v['app'])){
				$app = $v['app'];
				$v = self::fixed_vars($map_pattern_keys,$v);
				$v['name'] = isset($v['name']) ? $v['name'] : $k;
				$v['app'] = $app;
				$v['url'] = $k;
				$patterns[$k.'.+'] = $v;
			}else{
				if(isset($v['patterns'])){
					$kurl = is_int($k) ? '' : ($k.(empty($k) ? '' : '/'));
					$kpattern = $v['patterns'];
					unset($v['patterns']);
					
					foreach($kpattern as $pk => $pv){
						$patterns[$kurl.$pk] = self::fixed_vars($map_pattern_keys,$v,$pv);
					}
				}else{
					$patterns[$k] = self::fixed_vars($map_pattern_keys,$v);
				}
			}
		}
		$map['patterns'] = self::map_patterns($patterns);		
		return $map;
	}
	private static function map_patterns($patterns){
		$map_pattenrs = [];
		$http = self::$app_url;
		$https = str_replace('http://','https://',self::$app_url);
		$conf_secure = (\ebi\Conf::get('secure',true) === true);		
		
		$pattern_id = 1;
		foreach($patterns as $k => $v){
			$v['pattern_id'] = $pattern_id++;
				
			if(!isset($v['name'])){
				$v['name'] = (empty($k) ? 'index' : $k);
			}
			if(isset($v['action']) && is_string($v['action']) && strpos($v['action'],'::') === false){
				foreach(self::fixed_automap($k,$v['action'],$v['name']) as $murl => $am){
					$vam = array_merge($v,$am);
					list($vam['format'],$vam['num']) = self::url_format_func($murl,$vam['secure'],$conf_secure,$https,$http);
					$map_pattenrs[$murl] = $vam;
				}
			}else{
				list($v['format'],$v['num']) = self::url_format_func($k,$v['secure'],$conf_secure,$https,$http);
				$map_pattenrs[$k] = $v;
			}
		}
		uasort($map_pattenrs,function($a,$b){
			return (strlen($a['format']) < strlen($b['format']));
		});
		return $map_pattenrs;
	}
	private static function to_instance($package){
		if(is_object($package)) return $package;
		$package = str_replace('.','\\',$package);
		if($package[0] == '\\') $package = substr($package,1);
		$r = new \ReflectionClass($package);
		return $r->newInstance();
	}
}
