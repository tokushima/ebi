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
	private static $package_media_url = 'package/resources/media';
	
	private static $url_pattern = [];
	private static $selected_class_pattern = [];
	private static $workgroup;
	
	private static $is_get_map = false;
	private static $map = [];
	
	private static $template;
	
	/**
	 * アプリケーションのURL
	 * @return string
	 */
	public static function app_url(){
		return self::$app_url;
	}
	/**
	 * メディアファイルのベースURL
	 * @return string
	 */
	public static function media_url(){
		return self::$media_url;
	}
	/**
	 * 定義されたURLパターン
	 * @return string{}
	 */
	public static function url_pattern(){
		return self::$url_pattern;
	}
	/**
	 * 選択されたマップと同じクラスを持つURLパターン
	 * @return string{}
	 */
	public static function selected_class_pattern(){
		return self::$selected_class_pattern;
	}
	/**
	 * 現在のワークグループ
	 * @return string
	 */
	public static function workgroup(){
		return self::$workgroup;
	}
	/**
	 * mapsを取得する
	 * @param string $file
	 * @return array
	 */
	public static function get_map($file){
		self::$is_get_map = true;

		ob_start();
			include($file);
		ob_end_clean();
		
		return self::$map;
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
	 *  
	 * map_nameが配列の場合は一つ目がmap_nameとし残りをpatternに渡す値としてvarsで定義された名前を指定する
	 *  ext.. ['ptn1',['var1','var2']]
	 * 
	 * @param string $map_name
	 * @param  array $vars
	 * @param array $pattern
	 */
	private static function map_redirect($map_name,$vars=[],$pattern=[]){
		self::terminate();
		
		$args = [];
		$params = [];
		$name = null;
		$query = '';
		
		if(is_array($map_name)){
			$name = array_shift($map_name);
			$params = $map_name;
		}else{
			$name = $map_name;
		}
		if(empty($name)){
			\ebi\HttpHeader::redirect_referer();
		}
		foreach($params as $vn){
			if(is_string($vn) && isset($vn[0]) && $vn[0] == '@'){
				$vnm = substr($vn,1);

				if(!isset($vars[$vnm])){
					throw new \ebi\exception\InvalidArgumentException('variable '.$vnm.' not found');
				}
				$args[] = $vars[$vnm];
			}else{
				$args[] = $vn;
			}
		}
		if(strpos($name,'://') === false && array_key_exists('@',$pattern) && isset(self::$selected_class_pattern[$name][sizeof($args)])){
			$name = self::$selected_class_pattern[$name][sizeof($args)]['name'];
		}
		if(strpos($name,'://') !== false){
			\ebi\HttpHeader::redirect($name);
		}
		if(array_key_exists('qs',$pattern) && is_array($pattern['qs'])){
			$qs = [];
			
			foreach($pattern['qs'] as $k => $v){
				if(substr($v,0,1) == '@'){
					$qs[$k] = isset($vars[substr($v,1)]) ? $vars[substr($v,1)] : null;
				}
			}
			$query = '?'.http_build_query($qs);
		}
		if(isset(self::$url_pattern[$name][sizeof($args)])){
			$format = self::$url_pattern[$name][sizeof($args)];
			\ebi\HttpHeader::redirect((empty($args) ? $format : vsprintf($format,$args)).$query);
		}
		throw new \ebi\exception\InvalidArgumentException('map `'.$name.'` not found');
	}
	
	/**
	 * アプリケーションを実行する
	 * @param mixed{} $map
	 */
	public static function app($map=[]){
		if(empty($map)){
			$map = ['patterns'=>[''=>['action'=>'ebi.Dt','mode'=>'@dev']]];
		}else if(is_string($map)){
			$map = ['patterns'=>[''=>['action'=>$map]]];
		}else if(is_array($map) && !isset($map['patterns'])){
			$map = ['patterns'=>$map];
		}
		if(!isset($map['patterns']) || !is_array($map['patterns'])){
			throw new \ebi\exception\InvalidArgumentException('patterns not found');
		}
		
		$entry_file = null;
		foreach(debug_backtrace(false) as $d){
			if($d['file'] !== __FILE__){
				$entry_file = basename($d['file']);
				break;
			}
		}
		
		/**
		 * アプリケーションURL 
		 * 最後尾に「*」で実行エントリに自動変換
		 * http://localhost:8000/* => http://localhost:8000/api
		 * 
		 * 「**」でエントリファイル名に変換される
		 * http://localhost:8000/** => http://localhost:8000/api.php
		 * 
		 */
		$app_url = \ebi\Conf::get('app_url');
		
		if(is_array($app_url)){
			if(isset($app_url[$entry_file])){
				$app_url = $app_url[$entry_file];
			}else if(isset($app_url['*'])){
				$app_url = $app_url['*'];
			}else{
				$app_url = null;
			}
		}
		self::$app_url = $app_url;
		
		/**
		 * メディアファイルのベースURL
		 * http://localhost:8000/resources/media
		 */
		self::$media_url = \ebi\Conf::get('media_url');
		
		if(empty(self::$app_url)){
			$host = \ebi\Request::host();
			self::$app_url = (empty($host) ? 'http://localhost:8000/' : $host.'/').$entry_file;
		}else if(substr(self::$app_url,-1) == '*'){
			self::$app_url = substr(self::$app_url,0,-1);
			self::$app_url = (substr(self::$app_url,-1) == '*') ? 
				substr(self::$app_url,0,-1).$entry_file :
				self::$app_url.substr($entry_file,0,-4);
		}
		self::$app_url = \ebi\Util::path_slash(str_replace('https://','http://',self::$app_url),null,true);
		
		if(empty(self::$media_url)){
			$media_path = preg_replace('/\/[^\/]+\.php[\/]$/','/',self::$app_url);
			self::$media_url = $media_path.'resources/media/';
		}
		self::$media_url = \ebi\Util::path_slash(self::$media_url,null,true);
		/**
		 * テンプレートファイルのディレクトリ
		 */
		self::$template_path = \ebi\Util::path_slash(\ebi\Conf::get('template_path',\ebi\Conf::resource_path('templates')),null,true);
		
		$automap_idx = 1;
		self::$map['patterns'] = self::expand_patterns('',$map['patterns'], [], $automap_idx);
		unset($map['patterns']);
		self::$map = array_merge(self::$map,$map);
		self::$workgroup = (array_key_exists('workgroup',self::$map)) ? self::$map['workgroup'] : basename($entry_file,'.php');
		
		$http = self::$app_url;
		$https = str_replace('http://','https://',self::$app_url);
		
		/**
		 * HTTPSを有効にするか (falseの場合、mapのsecureフラグもすべてfalseとなる)
		 */
		$conf_secure = (\ebi\Conf::get('secure',true) === true);
		$map_secure = (array_key_exists('secure',self::$map) && self::$map['secure'] === true);

		$url_format_func = function($url,$map_secure,$conf_secure,$https,$http){
			$num = 0;
			$format = \ebi\Util::path_absolute(
				(($conf_secure && $map_secure === true) ? $https : $http),
				(empty($url)) ? '' : substr(preg_replace_callback("/([^\\\\])(\(.*?[^\\\\]\))/",function($n){return $n[1].'%s';},' '.$url,-1,$num),1)
			);
			return [str_replace(['\\\\','\\.','_ESC_'],['_ESC_','.','\\'],$format),$num];
		};
		
		foreach(self::$map['patterns'] as $k => $v){
			list(self::$map['patterns'][$k]['format'],self::$map['patterns'][$k]['num']) = $url_format_func(
				$k,
				(array_key_exists('secure',$v) ? ($v['secure'] === true) : $map_secure),
				$conf_secure,
				$https,
				$http
			);
		}
		krsort(self::$map['patterns']);
		
		if(self::$is_get_map){
			self::$is_get_map = false;
			return;
		}		
		$pathinfo = preg_replace("/(.*?)\?.*/","\\1",(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : ''));
		
		if(preg_match('/^\/'.preg_quote(self::$package_media_url,'/').'\/(\d+)\/(.+)$/',$pathinfo,$m)){	
			foreach(self::$map['patterns'] as $p){
				if(isset($p['@']) && isset($p['idx']) && (int)$p['idx'] === (int)$m[1] && is_file($file=($p['@'].'/resources/media/'.$m[2]))){
					\ebi\HttpFile::attach($file);
				}
			}
			\ebi\HttpHeader::send_status(404);
			return self::terminate();
		}
		foreach(self::$map['patterns'] as $k => $pattern){
			if(preg_match('/^'.(empty($k) ? '' : '\/').str_replace(['\/','/','@#S'],['@#S','\/','\/'],$k).'[\/]{0,1}$/',$pathinfo,$param_arr)){
				if(array_key_exists('secure',$pattern) && $pattern['secure'] === true && $conf_secure !== false){
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
				self::$template = new \ebi\Template();
				
				try{
					$funcs = $class = $method = $template = $ins = null;
					$exception = null;
					$has_flow_plugin = false;
					$result_vars = $plugins = [];
					$accept_debug = (
						/**
						 * Accept: application/debug を有効にする
						 * ヘッダにAcceptを指定した場合に出力を標準(JSON)とする
						 * テンプレートやリダイレクト、出力プラグインを無視する
						 * @param boolean
						 */
						\ebi\Conf::get('accept_debug',false) &&
						strpos(strtolower((new \ebi\Env())->get('HTTP_ACCEPT')),'application/debug') !== false
					);
					array_shift($param_arr);
					
					if(array_key_exists('action',$pattern)){
						if(is_string($pattern['action'])){
							list($class,$method) = explode('::',$pattern['action']);							
						}else if(is_callable($pattern['action'])){
							$funcs = $pattern['action'];
						}else{
							throw new \ebi\exception\InvalidArgumentException($pattern['name'].' action invalid');
						}
					}
					foreach(self::$map['patterns'] as $m){
						self::$url_pattern[$m['name']][$m['num']] = $m['format'];
						
						if(array_key_exists('@',$pattern) && array_key_exists('@',$m) && $pattern['idx'] == $m['idx']){
							list(,$mm) = explode('::',$m['action']);
							self::$selected_class_pattern[$mm][$m['num']] = ['format'=>$m['format'],'name'=>$m['name']];
						}
					}
					if(array_key_exists('vars',self::$map) && is_array(self::$map['vars'])){
						$result_vars = self::$map['vars'];
					}
					if(array_key_exists('vars',$pattern) && is_array($pattern['vars'])){
						$result_vars =  array_merge($result_vars,$pattern['vars']);
					}
					if(array_key_exists('redirect',$pattern)){
						self::map_redirect($pattern['redirect'],$result_vars,$pattern);
					}
					foreach(array_merge(
						(array_key_exists('plugins',self::$map) ? (is_array(self::$map['plugins']) ? self::$map['plugins'] : [self::$map['plugins']]) : []),
						(array_key_exists('plugins',$pattern) ? (is_array($pattern['plugins']) ? $pattern['plugins'] : [$pattern['plugins']]) : [])
					) as $m){
						$o = \ebi\Util::strtoinstance($m);
						self::set_class_plugin($o);
						$plugins[] = $o;
					}
					if(!isset($funcs) && isset($class)){
						$ins = \ebi\Util::strtoinstance($class);
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
								$o = \ebi\Util::strtoinstance($m);
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
						/**
						 * 前処理
						 */
						self::call_class_plugin_funcs('before_flow_action');
					}
					if($has_flow_plugin){
						$ins->before();
						$before_redirect = $ins->get_before_redirect();
						
						if(isset($before_redirect)){
							self::map_redirect($before_redirect,$result_vars,$pattern);
						}
					}
					if(isset($funcs)){
						try{
							$action_result_vars = call_user_func_array($funcs,$param_arr);
							
							if(is_array($action_result_vars)){
								$result_vars = array_merge($result_vars,$action_result_vars);
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
						if(!empty($template)){
							$pattern['template'] = $template;
						}
						$result_vars = array_merge($result_vars,$ins->get_after_vars());
						$after_redirect = $ins->get_after_redirect();
						
						if(isset($after_redirect) && !array_key_exists('after',$pattern) && !array_key_exists('cond_after',$pattern)){
							$pattern['after'] = $after_redirect;
						}
					}
					if(self::has_class_plugin('after_flow_action')){
						/**
						 * 後処理
						 */
						self::call_class_plugin_funcs('after_flow_action');
					}
					if(isset($exception)){
						throw $exception;
					}
					\ebi\Exceptions::throw_over();
					
					if(!$accept_debug){
						if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'){
							if(array_key_exists('post_cond_after',$pattern) && is_array($pattern['post_cond_after'])){
								foreach($pattern['post_cond_after'] as $cak => $cav){
									if(isset($result_vars[$cak])){
										self::map_redirect($cav,$result_vars,$pattern);
									}
								}
							}
							if(array_key_exists('post_after',$pattern)){
								self::map_redirect($pattern['post_after'],$result_vars,$pattern);
							}
						}
						if(array_key_exists('cond_after',$pattern) && is_array($pattern['cond_after'])){
							foreach($pattern['cond_after'] as $cak => $cav){
								if(isset($result_vars[$cak])){
									self::map_redirect($cav,$result_vars,$pattern);
								}
							}
						}
						if(array_key_exists('after',$pattern)){
							self::map_redirect($pattern['after'],$result_vars,$pattern);
						}
					
						if(array_key_exists('template',$pattern)){
							if(array_key_exists('template_super',$pattern)){
								self::$template->template_super(\ebi\Util::path_absolute(self::$template_path,$pattern['template_super']));
							}
							return self::template($result_vars,$pattern,$ins,\ebi\Util::path_absolute(self::$template_path,$pattern['template']));
						}else if(isset($template)){
							return self::template($result_vars,$pattern,$ins,\ebi\Util::path_absolute(self::$template_path,$template));
						}else if(
							array_key_exists('@',$pattern)
							&& is_file($t=\ebi\Util::path_absolute(self::$template_path,$pattern['name']).'.html')
						){
							return self::template($result_vars,$pattern,$ins,$t);					
						}else if(
							array_key_exists('@',$pattern)
							&& is_file($t=($pattern['@'].'/resources/templates/'.preg_replace('/^.+::/','',$pattern['action'].'.html')))
						){
							return self::template($result_vars,$pattern,$ins,$t,self::$app_url.self::$package_media_url.'/'.$pattern['idx']);
						}else if(
							array_key_exists('find_template',self::$map) && self::$map['find_template'] === true
							&& is_file($t=\ebi\Util::path_absolute(self::$template_path,$pattern['name'].'.html'))
						){
							return self::template($result_vars,$pattern,$ins,$t);
						}else if(self::has_class_plugin('flow_output')){
							/**
							 * 結果を出力する
							 * @param mixed{} $result_vars actionで返却された変数
							 */
							self::call_class_plugin_funcs('flow_output',$result_vars);
							return self::terminate();
						}
					}
					
					\ebi\HttpHeader::send('Content-Type','application/json');
					print(\ebi\Json::encode(['result'=>\ebi\Util::to_primitive($result_vars)]));
					return self::terminate();
				}catch(\Exception $e){
					\ebi\FlowInvalid::set($e);
					\ebi\Dao::rollback_all();
					\ebi\Log::warning($e);
					
					if(isset($pattern['error_status'])){
						\ebi\HttpHeader::send_status($pattern['error_status']);
					}else if(isset(self::$map['error_status'])){
						\ebi\HttpHeader::send_status(self::$map['error_status']);
					}					
					if(isset($pattern['vars']) && !empty($pattern['vars']) && is_array($pattern['vars'])){
						$result_vars = array_merge($result_vars,$pattern['vars']);
					}
					
					if(!$accept_debug){
						if(isset($pattern['@']) && is_file($t=$pattern['@'].'/resources/templates/error.html')){
							return self::template($result_vars,$pattern,$ins,$t,self::$app_url.self::$package_media_url.'/'.$pattern['idx']);
						}else if(array_key_exists('error_redirect',$pattern)){
							return self::map_redirect($pattern['error_redirect'],[],$pattern);
						}else if(array_key_exists('error_template',$pattern)){
							return self::template($result_vars,$pattern,$ins,\ebi\Util::path_absolute(self::$template_path,$pattern['error_template']));
						}else if(array_key_exists('error_redirect',self::$map)){
							return self::map_redirect(self::$map['error_redirect'],[],$pattern);
						}else if(array_key_exists('error_template',self::$map)){
							return self::template($result_vars,$pattern,$ins,\ebi\Util::path_absolute(self::$template_path,self::$map['error_template']));
						}else if(self::has_class_plugin('flow_exception')){
							/**
							 * 例外発生時の処理・出力
							 * @param \Exception $e 発生した例外
							 */
							self::call_class_plugin_funcs('flow_exception',$e);
							return self::terminate();
						}else if(self::has_class_plugin('flow_output')){
							self::call_class_plugin_funcs('flow_output',['error'=>['message'=>$e->getMessage()]]);
							return self::terminate();
						}
					}
					
					/**
					 *  Error Json出力時にException traceも出力するフラグ
					 */
					$trace = \ebi\Conf::get('exception_trace',false);
					$message = [];
					
					foreach(\ebi\FlowInvalid::get() as $g => $e){
						$em = [
							'message'=>$e->getMessage(),
							'type'=>basename(str_replace("\\",'/',get_class($e)))
						];
						if($trace){
							$em['trace'] = $e->getTraceAsString();
						}
						if(!empty($g)){
							$em['group'] = $g;
						}
						$message[] = $em;
					}
					\ebi\HttpHeader::send('Content-Type','application/json');
					print(json_encode(['error'=>$message]));
					
					return self::terminate();
				}
			}
		}
		if(array_key_exists('nomatch_redirect',self::$map) && strpos(self::$map['nomatch_redirect'],'://') === false){
			foreach(self::$map['patterns'] as $m){
				if(self::$map['nomatch_redirect'] == $m['name']){
					self::$url_pattern[$m['name']][$m['num']] = $m['format'];
					break;
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
	
	private static function expand_patterns($pk,$patterns,$extends,&$automap_idx){
		$result = [];
		$ext_arr = ['plugins'=>[],'vars'=>[]];
				
		foreach($ext_arr as $k =>$v){
			if(array_key_exists($k,$extends)){
				$ext_arr[$k] = $extends[$k];
				unset($extends[$k]);
			}
		}
		foreach($patterns as $k => $v){
			if(is_callable($v)){
				$v = ['action'=>$v];
			}
			if(!isset($v['mode']) || \ebi\Conf::in_mode($v['mode'])){
				if(!empty($extends)){
					$v = array_merge($extends,$v);
				}
				foreach($ext_arr as $ek => $ev){
					if(!empty($ev)){
						$v[$ek] = array_key_exists($ek,$v) ? (is_array($v[$ek]) ? $v[$ek] : [$v[$ek]]) : [];
						$v[$ek] = array_merge($ev,$v[$ek]);
					}
				}
				$pt = (empty($pk) ? '' : $pk.'/').$k;
		
				if(array_key_exists('patterns',$v)){
					if(!array_key_exists('mode',$v) || \ebi\Conf::in_mode($v['mode'])){
						$vp = $v['patterns'];
						unset($v['patterns']);
						$result = array_merge($result,self::expand_patterns($pt,$vp,$v,$automap_idx));
					}
				}else{
					if(!isset($v['name'])){
						$v['name'] = $pt;
					}
					if(isset($v['action'])){
						if(!is_callable($v['action']) && strpos($v['action'],'::') === false){
							foreach(self::automap($pt,$v['action'],$v['name'],$automap_idx++) as $ak => $av){
								$result[$ak] = array_merge($v,$av);
							}
						}else{
							$result[$pt] = $v;
						}
					}else{
						$result[$pt] = $v;
					}
				}
			}
		}
		return $result;
	}
	private static function automap($url,$class,$name,$idx){
		$result = [];
		try{
			$r = new \ReflectionClass(str_replace('.','\\',$class));
			$d = substr($r->getFilename(),0,-4);
	
			foreach($r->getMethods(\ReflectionMethod::IS_PUBLIC) as $m){
				if(!$m->isStatic() && substr($m->getName(),0,1) != '_'){
					$suffix = '';
					$auto_anon = \ebi\Annotation::get_method($r->getName(),$m->getName(),'automap');
					
					if(is_array($auto_anon)){
						$base_name = $m->getName();
	
						if(isset($auto_anon['suffix'])){
							$suffix = $auto_anon['suffix'];
							unset($auto_anon['suffix']);
						}
						if(isset($auto_anon['name'])){
							$base_name = $auto_anon['name'];
							unset($auto_anon['name']);
						}
						$murl = $url.(($m->getName() == 'index') ? '' : (($url == '') ? '' : '/').$base_name).str_repeat('/(.+)',$m->getNumberOfRequiredParameters());
						
						$result[$murl.$suffix] = [
							'name'=>$name.'/'.$base_name,
							'action'=>$class.'::'.$m->getName(),
							'@'=>$d,
							'idx'=>$idx,
						];
						if(!empty($auto_anon)){
							$result[$murl.$suffix] = array_merge($result[$murl.$suffix],$auto_anon);
						}
					}
				}
			}
		}catch(\ReflectionException $e){
			throw new \ebi\exception\InvalidArgumentException($class.' not found');
		}
		return $result;
	}
}
