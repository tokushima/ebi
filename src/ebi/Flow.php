<?php
namespace ebi;
/**
 * リクエスト処理ワークフロー
 * @author tokushima
 *
 */
class Flow{
	use \ebi\Plugin;

	private $app_url;
	private $media_url;
	private $template_path;
	private $apps_path;
	private $package_media_url = 'package/resources/media';	
	
	private $url_pattern = [];
	private $selected_class_pattern = [];
	private $selected_pattern;
	
	private static $is_get_branch = false;
	private static $branch_map = [];
	
	private static $is_get_map = false;
	private static $map = [];
	
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
			$self = new self();
			$self->execute($map);
		}
	}
	
	private function template(array $vars,$ins,$path,$media=null){
		if(empty($media)) $media = $this->media_url;
		$this->template->set_object_plugin(new \ebi\FlowInvalid());
		$this->template->set_object_plugin(new \ebi\Paginator());
		$this->template->media_url($media);
		$this->template->cp($vars);
		$this->template->vars('t',new \ebi\FlowHelper($this->app_url,$this->media_url,$this->url_pattern,$this->selected_pattern,$this->selected_class_pattern,$ins));
		$src = $this->template->read($path);
		
		print($src);
		$this->terminate();
		exit;
	}
	private function redirect($url,$args=array()){
		$this->terminate();
		if(is_array($url)){
			$tmp = array_shift($url);
			if(empty($args)){
				$args = $url;
			}
			$url = $tmp;
		}
		if(strpos($url,'://') !== false){
			\ebi\HttpHeader::redirect($url);
		}
		if(isset($this->url_pattern[$url][sizeof($args)])){
			$format = $this->url_pattern[$url][sizeof($args)];
			\ebi\HttpHeader::redirect(empty($args) ? $format : vsprintf($format,$args));
		}
		throw new \InvalidArgumentException('map `'.$url.'` not found');
	}
	private function map_redirect($map,array $vars,$pattern){
		if(is_array($map) && !isset($map[0])){
			$bool = false;
			foreach($map as $k => $a){
				if(array_key_exists($k,$vars)){
					$map = $a;
					$bool = true;
					break;
				}
			}
 			if(!$bool){
 				return;
 			}
		}
		$name = is_string($map) ? $map : (is_array($map) ? array_shift($map) : null);
		$var_names = (!empty($map) && is_array($map)) ? $map : [];
		$args = [];
		
		foreach($var_names as $n){
			if(!isset($vars[$n])){
				throw new \InvalidArgumentException('variable '.$n.' not found');
			}
			$args[$n] = $vars[$n];
		}
		if(isset($pattern['branch']) && strpos($name,'#') === false){
			$name = $pattern['branch'].'#'.$name;
		}
		if(isset($pattern['@'])){
			if(isset($this->selected_class_pattern[$name][sizeof($args)])){
				$name = $this->selected_class_pattern[$name][sizeof($args)]['name'];
			}
		}
		if(empty($name)){
			\ebi\HttpHeader::redirect_referer();
		}
		$this->redirect($name,$args);
	}
	private function execute($map){
		/**
		 * アプリケーションのベースURL
		 */
		$this->app_url = \ebi\Conf::get('app_url');
		/**
		 * メディアファイルのベースURL
		 */
		$this->media_url = \ebi\Conf::get('media_url');
		
		if(empty($this->app_url)){
			$host = \ebi\Conf::get('host',\ebi\Request::host());
			$entry_file = null;
			foreach(debug_backtrace(false) as $d){
				if($d['file'] !== __FILE__){
					$entry_file = str_replace("\\",'/',$d['file']);
					break;
				}
			}
			if(empty($host)){
				$this->app_url = 'http://localhost:8000/'.basename($entry_file);
			}else{
				$hasport = (boolean)preg_match('/:\d+/',$host);
				$entry = preg_replace("/.+\/workspace(\/.+)/","\\1",$entry_file);
				$this->app_url = $host.'/'.($hasport ? basename($entry) : $entry);
				$this->media_url = dirname($this->app_url).'/resources/media/';
			}
		}else if(substr($this->app_url,-1) == '*'){
			$entry_file = null;
			foreach(debug_backtrace(false) as $d){
				if($d['file'] !== __FILE__){
					$entry_file = str_replace("\\",'/',$d['file']);
					break;
				}
			}
			$this->app_url = substr($this->app_url,0,-1).basename($entry_file);
		}	
		$this->app_url = \ebi\Util::path_slash(str_replace('https://','http://',$this->app_url),null,true);

		if(empty($this->media_url)){
			$media_path = preg_replace('/\/[^\/]+\.php[\/]$/','/',$this->app_url);
			$this->media_url = $media_path.'resources/media/';
		}
		$this->media_url = \ebi\Util::path_slash($this->media_url,null,true);
		/**
		 * apps(action appのファイル群)のディレクトリパス
		 */
		$this->apps_path = \ebi\Util::path_slash(\ebi\Conf::get('apps_path',getcwd().'/apps/'),null,true);
		$this->template_path = \ebi\Util::path_slash(\ebi\Conf::get('template_path',\ebi\Conf::resource_path('templates')),null,true);
		$this->template = new \ebi\Template();
		
		if(is_string($map)){
			$map = ['patterns'=>[''=>['action'=>$map]]];
		}
		if(!isset($map['patterns']) || !is_array($map['patterns'])){
			throw new \InvalidArgumentException('pattern not found');
		}
		$result_vars = array();
		$pathinfo = preg_replace("/(.*?)\?.*/","\\1",(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : ''));
		self::$map = $this->read($map);
		
		if(self::$is_get_map){
			self::$is_get_map = false;
			return;
		}
		if(preg_match('/^\/'.preg_quote($this->package_media_url,'/').'\/(\d+)\/(.+)$/',$pathinfo,$m)){
			foreach(self::$map['patterns'] as $p){
				if((int)$p['pattern_id'] === (int)$m[1] && isset($p['@']) && is_dir($dir=($p['@'].'/resources/media/'.$m[2]))){
					\ebi\HttpFile::attach($dir);
				}
			}
			\ebi\HttpHeader::send_status(404);
			exit;
		}
		foreach(self::$map['patterns'] as $k => $pattern){
			if(preg_match('/^'.(empty($k) ? '' : '\/').str_replace(array('\/','/','@#S'),array('@#S','\/','\/'),$k).'[\/]{0,1}$/',$pathinfo,$param_arr)){
				if(isset($pattern['mode']) && !empty($pattern['mode'])){
					$mode = \ebi\Conf::appmode();
					$mode_alias = \ebi\Conf::get('mode');
					$bool = false;
					foreach(explode(',',$pattern['mode']) as $m){
						foreach((
								(substr(trim($m),0,1) == '@' && isset($mode_alias[substr(trim($m),1)])) ?
								explode(',',$mode_alias[substr(trim($m),1)]) :
								array($m)
						) as $me){
							if($mode == trim($me)){
								$bool = true;
								break;
							}
						}
					}
					if(!$bool) break;
				}
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
					$this->media_url = str_replace('http://','https://',$this->media_url);
				}
				try{
					$funcs = $class = $method = $template = $ins = null;
					$exception = null;
					$has_flow_plugin = false;
					$result_vars = $plugins = [];
					array_shift($param_arr);
					$this->selected_pattern = $pattern;
					
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
						$this->url_pattern[$m['name']][$m['num']] = $m['format'];
						
						if(!empty($class) && isset($pattern['@']) && isset($m['@']) && strpos($m['action'],$class.'::') === 0){
							$this->selected_class_pattern[substr($m['action'],strlen($class.'::'))][$m['num']] = ['format'=>$m['format'],'name'=>$m['name']];
						}
					}
					if(isset($pattern['branch'])){
						foreach(self::$map['branch'][$pattern['branch']] as $k => $v){
							if(!empty($v)) self::$map[$k] = $v;
						}
					}
					if(isset($pattern['redirect'])){
						$this->redirect($pattern['redirect']);
					}
					foreach(array_merge(self::$map['plugins'],$pattern['plugins']) as $m){
						$o = $this->to_instance($m);
						$this->set_object_plugin($o);
						$plugins[] = $o;
					}
					if(!isset($funcs) && isset($class)){
						$ins = $this->to_instance($class);
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
								$o = $this->to_instance($m);
								$plugins[] = $o;
								$this->set_object_plugin($o);
							}
							$ins->set_pattern($this->selected_pattern);
						}
						if(in_array('ebi\\Plugin',$traits)){
							foreach($plugins as $o){
								$ins->set_object_plugin($o);
							}
						}
						$funcs = [$ins,$method];
					}
					foreach($plugins as $o){
						$this->template->set_object_plugin($o);
					}
					if($this->has_object_plugin('before_flow_action')){
						$this->call_object_plugin_funcs('before_flow_action');
					}					
					if($has_flow_plugin){
						$ins->before();
						$before_redirect = $ins->get_before_redirect();
						if(isset($before_redirect)){
							$this->map_redirect($before_redirect,[],$pattern);
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
							$this->template->put_block(\ebi\Util::path_absolute($this->template_path,$ins->get_template_block()));
						}
						$template = $ins->get_template();
						$result_vars = array_merge($result_vars,$ins->get_after_vars());							
						$after_redirect = $ins->get_after_redirect();
						if(isset($after_redirect) && !isset($pattern['after'])){
							$pattern['after'] = $after_redirect;
						}
					}
					
					if($this->has_object_plugin('after_flow_action')){
						$result_vars = array_merge($result_vars,$this->call_object_plugin_funcs('after_flow_action'));
					}
					if(isset($exception)){
						throw $exception;
					}
					\ebi\Exceptions::throw_over();
					
					if(isset($pattern['vars']) && is_array($pattern['vars'])){
						$result_vars = array_merge($result_vars,$pattern['vars']);
					}
					if(isset($pattern['post_after']) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'){
						$this->map_redirect($pattern['post_after'],$result_vars,$pattern);
					}
					if(isset($pattern['after'])){
						$this->map_redirect($pattern['after'],$result_vars,$pattern);
					}
					if(isset($pattern['template'])){
						if(isset($pattern['template_super'])){
							$this->template->template_super(\ebi\Util::path_absolute($this->template_path,$pattern['template_super']));
						}
						$this->template($result_vars,$ins,\ebi\Util::path_absolute($this->template_path,$pattern['template']));
					}else if(isset($template)){
						$this->template($result_vars,$ins,\ebi\Util::path_absolute($this->template_path,$template));
					}else if(
						isset($pattern['@'])
						&& is_file($t=($pattern['@'].'/resources/templates/'.preg_replace('/^.+::/','',$pattern['action']).'.html'))
					){
						$this->template($result_vars,$ins,$t,$this->app_url.$this->package_media_url.'/'.$pattern['pattern_id']);
					}else if(
						self::$map['find_template'] !== false
						&& is_file($t=\ebi\Util::path_absolute($this->template_path,$pattern['name'].'.html'))
					){
						$this->template($result_vars,$ins,$t);
					}else if($this->has_object_plugin('flow_output')){
						$this->call_object_plugin_funcs('flow_output',$result_vars);
						return $this->terminate();
					}else{
						$to_array = function($value) use(&$to_array){
							switch(gettype($value)){
								case 'array':
									$list = array();
									foreach($value as $k => $v){
										$list[$k] = $to_array($v);
									}
									return $list;
								case 'object':
									$list = array();
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
						return $this->terminate();
					}
				}catch(\Exception $e){
					\ebi\FlowInvalid::set($e);
					
					if(!($e instanceof \ebi\Exceptions)){
						\ebi\Exceptions::add($e);
					}
					if(($level = \ebi\Conf::get('exception_log_level')) !== null && in_array($level,array('error','warn','info','debug'))){
						$es = ($e instanceof \ebi\Exceptions) ? $e : array($e);
						$ignore = \ebi\Conf::get('exception_log_ignore');
						foreach($es as $ev){
							$in = true;
							if(!empty($ignore)){
								foreach((is_array($ignore) ? $ignore : array($ignore)) as $p){
									if(($in = !(preg_match('/'.str_replace('/','\\/',$p).'/',(string)$ev))) === false) break;
								}
							}
							if($in) \ebi\Log::$level($ev);
						}
					}
					if($this->has_object_plugin('flow_exception')){
						$this->call_object_plugin_funcs('flow_exception',$e);
						\ebi\Dao::rollback_all();
					}
					if(isset($pattern['error_status'])){
						\ebi\HttpHeader::send_status($pattern['error_status']);
					}else if(isset(self::$map['error_status'])){
						\ebi\HttpHeader::send_status(self::$map['error_status']);
					}
					if(isset($pattern['vars']) && !empty($pattern['vars']) && is_array($pattern['vars'])){
						$result_vars = array_merge($result_vars,$pattern['vars']);
					}
					if(isset($pattern['@']) && is_file($t=$pattern['@'].'/resources/templates/error.html')){
						$this->template($result_vars,$ins,$t,$this->app_url.$this->package_media_url.'/'.$pattern['pattern_id']);
					}else if(isset($pattern['error_redirect'])){
						$this->redirect($pattern['error_redirect']);
					}else if(isset($pattern['error_template'])){
						$this->template($result_vars,$ins,\ebi\Util::path_absolute($this->template_path,$pattern['error_template']));
					}else if(isset(self::$map['error_redirect'])){
						$this->redirect(self::$map['error_redirect']);
					}else if(isset(self::$map['error_template'])){
						$this->template($result_vars,$ins,\ebi\Util::path_absolute($this->template_path,self::$map['error_template']));
					}else if($this->has_object_plugin('flow_output')){
						$this->call_object_plugin_funcs('flow_output',array('error'=>array('message'=>$e->getMessage())));
						return $this->terminate();
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
					
					return $this->terminate();
				}
			}
		}
		if(isset(self::$map['nomatch_redirect'])){
			if(strpos(self::$map['nomatch_redirect'],'://') === false){
				foreach(self::$map['patterns'] as $m){
					if(self::$map['nomatch_redirect'] == $m['name']){
						$this->url_pattern[$m['name']][$m['num']] = $m['format'];
						break;
					}
				}
			}
			$this->redirect(self::$map['nomatch_redirect']);
		}
		\ebi\HttpHeader::send_status(404);
		return $this->terminate();
	}
	private function terminate(){
		\ebi\FlowInvalid::clear();
		return;
	}
	private function read($map){
		$automap = function($url,$class,$name){
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
								$result[$murl.$suffix] = array(
									'name'=>$name.'/'.$base_name
									,'action'=>$class.'::'.$m->getName()
									,'@'=>$d
								);
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
		};
		$map_pattern_keys = [
				0=>['name','action','redirect',
					'media_path',
					'template','template_path','template_super',
					'error_redirect','error_status','error_template',
					'suffix','secure','mode','after','post_after',
					'summary',
				]
				,1=>['plugins','args','vars']
		];
		$root_keys = [
				0=>['media_path',
					'nomatch_redirect',
					'error_redirect','error_status','error_template',
					'secure','find_template',
				]
				,1=>['plugins','patterns']
		];
		$fixed_vars = function($fixed_keys,$map,$exmap=[]){
			$result = [];
			foreach($fixed_keys as $t => $keys){
				foreach($keys as $k){
					if($t == 0){
						$result[$k] = isset($exmap[$k]) ? $exmap[$k] : (isset($map[$k]) ? $map[$k] : null);
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
		};
		
		$map = $fixed_vars($root_keys,$map);
		foreach($map['patterns'] as $k => $v){
			if(is_int($k) || isset($map['patterns'][$k]['patterns'])){
				$kurl = is_int($k) ? '' : ($k.(empty($k) ? '' : '/'));
				$kpattern = $map['patterns'][$k]['patterns'];
				unset($map['patterns'][$k]['patterns']);
				
				foreach($kpattern as $pk => $pv){
					$map['patterns'][$kurl.$pk] = $fixed_vars($map_pattern_keys,$map['patterns'][$k],$pv);
				}
				if(!array_key_exists('',$kpattern)){
					unset($map['patterns'][$k]);
				}
			}else{
				if(isset($map['patterns'][$k]['app'])){
					$branch_path = $map['patterns'][$k]['app'];
					$name = isset($map['patterns'][$k]['name']) ? $map['patterns'][$k]['name'] : $k;
					unset($map['patterns'][$k]);

					if(is_file($f=$this->apps_path.$branch_path.'.php')){
						self::$is_get_branch = true;
						self::$branch_map = [];
						ob_start();
							$rtn = include($f);
						ob_end_clean();
						self::$is_get_branch = false;
						
						self::$branch_map = $fixed_vars($root_keys,$this->read(self::$branch_map));						
						foreach(array_keys(self::$branch_map['patterns']) as $bk){
							 $bm = $fixed_vars($map_pattern_keys,self::$branch_map['patterns'][$bk],self::$branch_map);
							 $bm['name'] = $name.'#'.$bm['name'];
							 $bm['branch'] = $branch_path;
							 $map['patterns'][$k.(empty($bk) ? '' :'/'.$bk)] = $bm;
						}
						unset(self::$branch_map['patterns']);
						$map['branch'][$branch_path] = self::$branch_map;
					}
				}else{
					$map['patterns'][$k] = $fixed_vars($map_pattern_keys,$map['patterns'][$k]);
				}
			}
		}
		$http = $this->app_url;
		$https = str_replace('http://','https://',$this->app_url);
		$conf_secure = (\ebi\Conf::get('secure',true) === true);
		$url_format_func = function($url,$map_secure) use($conf_secure,$https,$http){
			$num = 0;
			$format = \ebi\Util::path_absolute(
					(($conf_secure && $map_secure === true) ? $https : $http),
					(empty($url)) ? '' : substr(preg_replace_callback("/([^\\\\])(\(.*?[^\\\\]\))/",function($n){return $n[1].'%s';},' '.$url,-1,$num),1)
			);
			return [str_replace(array('\\\\','\\.','_ESC_'),array('_ESC_','.','\\'),$format),$num];
		};
		$patterns = $map['patterns'];
		$map['patterns'] = [];
		$pattern_id = 1;
		
		foreach($patterns as $k => $v){
			$v['pattern_id'] = $pattern_id++;
			if(!isset($v['name'])){
				$v['name'] = (empty($k) ? 'index' : $k);
			}
			if(isset($v['action']) && is_string($v['action']) && strpos($v['action'],'::') === false){				
				foreach($automap($k,$v['action'],$v['name']) as $murl => $am){
					$vam = array_merge($v,$am);
					list($vam['format'],$vam['num']) = $url_format_func($murl,$vam['secure']);
					$map['patterns'][$murl] = $vam;
				}
			}else{
				list($v['format'],$v['num']) = $url_format_func($k,$v['secure']);
				$map['patterns'][$k] = $v;
			}
		}
		uasort($map['patterns'],function($a,$b){
			return (strlen($a['format']) < strlen($b['format']));
		});
		return $map;
	}	
	private function to_instance($package){
		if(is_object($package)) return $package;
		$package = str_replace('.','\\',$package);
		if($package[0] == '\\') $package = substr($package,1);
		$r = new \ReflectionClass($package);
		return $r->newInstance();
	}
}
