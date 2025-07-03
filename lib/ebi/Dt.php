<?php
namespace ebi;
/**
 * 開発支援ツール
 */
class Dt extends \ebi\flow\Request{
	private string $entry;
	private static $mock = [];
	
	public function __construct(?string $entryfile=null){
		if(empty($entryfile)){
			$trace = debug_backtrace(false);
			krsort($trace);
			
			foreach($trace as $t){
				if(isset($t['class']) && $t['class'] == 'ebi\Flow'){
					$this->entry = $t['file'];
					break;
				}
			}
		}else{
			$entryfile = realpath($entryfile);
			$this->entry = $entryfile;
		}
		parent::__construct();
	}
	public function get_after_vars(): array{
		$vars = [
			'f'=>new \ebi\Dt\Helper(),
			'appmode'=>constant('APPMODE'),
		];
		return $vars;
	}
	/**
	 * @automap
	 */
	public function index(): array{
		$flow_output_maps = [];
		
		$map = \ebi\Flow::get_map($this->entry);
		$patterns = $map['patterns'];
		unset($map['patterns']);

		$req = new \ebi\Request();
		$target_version = (string)$req->in_vars('version');
		$file_version = date('Ymd',filemtime($this->entry));
		$version_list = [];
		$self_class = get_class($this);
		$class_name = function($name){
			return ($name[0] === '\\') ? substr($name,1) : $name;
		};
		
		foreach($patterns as $k => $m){
			foreach([
				'deprecated'=>false,
				'mode'=>null,
				'summary'=>null,
				'template'=>null,
				'version'=>null,
				'error'=>null,
			] as $i => $d){
				if(!isset($m[$i])){
					$m[$i] = $d;
				}
			}
			if(isset($m['action']) && is_string($m['action'])){
				[$m['class'], $m['method']] = explode('::',$m['action']);
			}
			if(!isset($m['class']) || $class_name($m['class']) != $self_class){
				try{
					$m['error'] = null;
					$m['url'] = $k;
					
					if(isset($m['method'])){
						$info = \ebi\Dt\Man::method_info($m['class'],$m['method']);
						
						if(!isset($m['version'])){
							$m['version'] = $info->version();
						}
						if(empty($m['summary'])){
							[$summary] = explode(PHP_EOL,$info->document());
							$m['summary'] = empty($summary) ? null : $summary;
						}
						if($m['deprecated'] || $info->opt('deprecated')){
							$m['deprecated'] = true;
						}
						if($m['deprecated'] || !empty($info->opt('first_depricated_date'))){
							$m['first_depricated_date'] = $info->opt('first_depricated_date', time());
						}
					}
				}catch(\Exception $e){
					$m['error'] = $e->getMessage();
				}
				foreach($m as $k => $v){
					if(is_array($v) && isset($map[$k]) && !empty($map[$k])){
						$m[$k] = array_merge($map[$k],$v);
					}else{
						if(!isset($v) && isset($map[$k])){
							$m[$k] = $map[$k];
						}
					}
				}
				
				if(!isset($m['version'])){
					$m['version'] = $file_version;
				}
				$version_list[$m['version']] = $m['version'];
				
				if(empty($target_version) || $m['version'] == $target_version){
					$flow_output_maps[$m['name']] = $m;
				}
			}
		}
		krsort($version_list);
		
		$entry_desc = (preg_match('/\/\*\*.+?\*\//s',\ebi\Util::file_read($this->entry),$m)) ?
			trim(preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$m[0]))) :
			'';
		
		return [
			'map_list'=>$flow_output_maps,
			'description'=>$entry_desc,
			'version'=>(empty($target_version) ? null : $target_version),
			'version_list'=>$version_list,
		];
	}	
	/**
	 * アクションのドキュメント
	 * @param string $name
	 * @context \ebi\man\DocInfo $method
	 * @automap
	 */
	public function index_action_doc(?string $name): array{
		$map = \ebi\Flow::get_map($this->entry);
		
		foreach($map['patterns'] as $m){
			if($m['name'] == $name){
				if(isset($m['action'])){
					if($m['action'] instanceof \Closure){
						$info = \ebi\Dt\Man::closure_info($m['action']);
					}else{
						[$m['class'], $m['method']] = explode('::',$m['action']);
						[,$user_model] = $this->get_login_annotation($m['class'],$m['method']);
						
						$info = \ebi\Dt\Man::method_info($m['class'],$m['method'],true,true);
						$info->set_opt('user_model',$user_model);
						foreach(['get_after_vars','get_after_vars_request'] as $mn){
							try{
								$ex_info = \ebi\Dt\Man::method_info($m['class'],$mn,true,true);
								
								foreach(['requests','contexts'] as $k){
									$info->set_opt($k,array_merge($ex_info->opt($k),$info->opt($k)));
								}
							}catch(\ReflectionException $e){
							}
						}						
					}
				}else{
					$info = new \ebi\Dt\DocInfo();
				}
				if(!empty($info->opt('deprecated')) || isset($m['deprecated'])){
					if(isset($m['deprecated'])){
						$deprecated = is_bool($m['deprecated']) ? time() : strtotime($m['deprecated']);
					}else{
						$deprecated = $info->opt('deprecated');
					}
					$info->set_opt('deprecated',$deprecated);
				}
				
				
				$info->set_opt('name',$name);
				$info->set_opt('url',$m['format']);
				
				$info->reset_params(array_slice($info->params(),0,$m['num']));
				
				return [
					'action_info'=>$info,
					'map'=>$m,
				];
			}
		}
		throw new \ebi\exception\NotFoundException();
	}
	
	private function get_login_annotation(string $class, string $method): array{
		$class = \ebi\Util::get_class_name($class);
		$login_anon = \ebi\Annotation::get_class($class,'login');

		if(isset($login_anon)){
			if($method == 'do_login' && ($class == \ebi\flow\Request::class || is_subclass_of($class, \ebi\flow\Request::class))){	
				return [false,$login_anon['type'] ?? null];
			}else{
				return [true,$login_anon['type'] ?? null];
			}
		}
		return [false,null];
	}
	
	/**
	 * クラスのドキュメント
	 * @automap
	 */
	public function class_doc(string $class): array{
		$info = \ebi\Dt\Man::class_info($class);
		
		return [
			'class_info'=>$info,
		];
	}
	/**
	 * クラスドメソッドのドキュメント
	 * @automap
	 */
	public function class_method_doc(string $class, string $method): array{
		$info = \ebi\Dt\Man::method_info($class,$method,true,true);
		
		return [
			'method_info'=>$info,
		];
	}
	
	/**
	 * class list
	 * @automap
	 */
	public function class_list(): array{
		$req = new \ebi\Request();
		$parent = (string)$req->in_vars('parent');
		$select = 'other';

		if(!empty($parent)){
			$parent = \ebi\Util::get_class_name($parent);
			
			switch($parent){
				case 'ebi\Dao':
					$select = 'model';
					break;
				case 'ebi\flow\Request':
					$select = 'request';
					break;
				default:
					$select = 'other';
			}
		}
		$list = [];
		foreach(self::classes($parent) as $info){
			$bool = true;
			
			if($select == 'other'){
				if(
					is_subclass_of($info['class'],'\ebi\Dao') ||
					is_subclass_of($info['class'],'\ebi\flow\Request')
				){
					$bool = false;
				}
			}
			if($bool){
				$class_info = \ebi\Dt\Man::class_info($info['class']);
				$list[$class_info->name()] = $class_info;
			}
		}
		ksort($list);
	
		return [
			'class_info_list'=>$list,
			'select'=>$select,
			'parent'=>$parent,
		];
	}
	/**
	 * Config
	 * @automap
	 */
	public function config_list(): array{
		$list = [];
	
		foreach(self::classes() as $info){
			$class_info = \ebi\Dt\Man::class_info($info['class']);
				
			if($class_info->has_opt('config_list')){
				$list[$class_info->name()] = $class_info;
			}
		}
		ksort($list);
		
		return [
			'class_info_list'=>$list,
		];
	}
		
	/**
	 * Mail Templates
	 * @context \ebi\Dt\DocInfo[] $template_list
	 * @automap
	 */
	public function mail_list(): array{
		$has_bh = false;
		
		try{
			if(\ebi\SmtpBlackholeDao::find_count() > 0){
				$has_bh = true;
			}
		}catch(\Exception $e){
		}
		
		$template_list = \ebi\Dt\Man::mail_template_list();
		
		foreach($template_list as $k => $info){
			$template_list[$k]->set_opt('use',false);
			$template_list[$k]->set_opt('count',0);
		}
		foreach(self::classes() as $class_info){
			$src = file_get_contents($class_info['filename']);
			
			foreach($template_list as $k => $info){
				if(strpos($src,$info->name()) !== false){
					$template_list[$k]->set_opt('use',true);
					$template_list[$k]->set_opt('count',0);
					
					if($has_bh){
						$template_list[$k]->set_opt(
							'count',
							\ebi\SmtpBlackholeDao::find_count(Q::eq('tcode',$info->opt('x_t_code')))
						);
						if(\ebi\SmtpBlackholeDao::find_count(Q::eq('tcode',$info->opt('x_t_code')),Q::gt('create_date',time() - 600)) > 0){
							$template_list[$k]->set_opt('new',true);
						}
					}
				}
			}
		}
		return [
			'template_list'=>$template_list,
			'has_bh'=>$has_bh,
		];
	}
	/**
	 * @automap
	 */
	public function mail_info(): array{
		$req = new \ebi\Request();
		$mail_info = $this->find_mail_template_info((string)$req->in_vars('tcode'));
		
		$method_list = [];
		$method_mail_info = null;
		$method_info = null;
		
		foreach(self::classes() as $class){
			if(strpos(\ebi\Util::file_read($class['filename']),$mail_info->name()) !== false){
				$ref_class = new \ReflectionClass($class['class']);
				
				foreach($ref_class->getMethods() as $ref_method){
					if(strpos(\ebi\Dt\Man::method_src($ref_method),$mail_info->name()) !== false){
						$method_info = \ebi\Dt\Man::method_info($ref_class->getName(),$ref_method->getName(),true);
						
						foreach($method_info->opt('mail_list') as $x_t_code => $mmi){
							if($x_t_code == $mail_info->opt('x_t_code')){
								$method_list[] = $method_info;
								$method_mail_info = $mmi;
								break;
							}
						}
					}
				}
			}
		}
		
		$params = [];
		foreach($method_list as $method){
			$desc = $method_mail_info->opt('description');
			
			if(empty(trim($desc))){
				$desc = $method->document();
			}
			$mail_info->set_opt('method_summary',$desc);
			
			foreach($method_mail_info->params() as $p){
				$params[$p->name()] = $p;
			}
		}
		foreach($params as $p){
			$mail_info->add_params($p);
		}
		return [
			'mail_info'=>$mail_info,
			'method_list'=>$method_list,
		];
	}
	private function find_mail_template_info(?string $tcode): \ebi\Dt\DocInfo{
		foreach(\ebi\Dt\Man::mail_template_list() as $info){
			if($info->opt('x_t_code') == $tcode){
				$path = \ebi\Conf::get(\ebi\Mail::class.'@resource_path',\ebi\Conf::resource_path('mail'));
				$xml = \ebi\Xml::extract(\ebi\Util::file_read(\ebi\Util::path_absolute($path,$info->name())),'mail');
				$body_xml = $xml->find_get('body');
		
				$signature = $body_xml->in_attr('signature');
				$signature_text = '';
				
				if(!empty($signature)){
					$sig_path = \ebi\Util::path_absolute($path,$signature);
					
					$sig_xml = \ebi\Xml::extract(file_get_contents($sig_path),'mail');
					$signature_text = \ebi\Util::plain_text(PHP_EOL.$sig_xml->find_get('signature')->value().PHP_EOL);
				}
				$info->set_opt('body',\ebi\Util::plain_text(PHP_EOL.$body_xml->value().PHP_EOL).$signature_text);
		
				try{
					$html_xml = $xml->find_get('html');
					
					if(empty($html_xml->in_attr('src'))){
						throw new \ebi\exception\RequiredException('attribute src is required for html');
					}
					$info->set_opt('html',\ebi\Util::file_read(\ebi\Util::path_absolute($path,$html_xml->in_attr('src'))));
				}catch(\ebi\exception\NotFoundException $e){
				}
				return $info;
			}
		}
		throw new \ebi\exception\NotFoundException();
	}
	/**
	 * @automap
	 */
	public function mail_blackhole(): array{
		$req = new \ebi\Request();
		$paginator = \ebi\Paginator::request($req);
		$list = \ebi\SmtpBlackholeDao::find_all(
			Q::eq('tcode', $req->in_vars('tcode')),
			$paginator,
			Q::order('-id')
		);
		
		$mail_info = new \ebi\Dt\DocInfo();
		foreach(\ebi\Dt\Man::mail_template_list() as $info){
			if($info->opt('x_t_code') == $req->in_vars('tcode')){
				$mail_info = $info;
				break;
			}
		}
		return $req->ar_vars([
			'mail_info'=>$mail_info,
			'paginator'=>$paginator,
			'object_list'=>$list,
		]);
	}
	/**
	 * @automap
	 */
	public function mail_view(): array{
		$req = new \ebi\Request();
		$obj = \ebi\SmtpBlackholeDao::find_get(
			Q::eq('tcode',$req->in_vars('tcode')),
			Q::eq('id',$req->in_vars('id'))
		);
		
		$mail_info = new \ebi\Dt\DocInfo();
		foreach(\ebi\Dt\Man::mail_template_list() as $info){
			if($info->opt('x_t_code') == $obj->tcode()){
				$mail_info = $info;
				break;
			}
		}
		
		return [
			'mail_info'=>$mail_info,
			'object'=>$obj,
		];
	}
	
	/**
	 * ライブラリ一覧
	 */
	public static function classes(?string $parent_class=null): \Generator{
		$include_path = [];
		
		if(is_dir(getcwd().DIRECTORY_SEPARATOR.'lib')){
			$include_path[] = realpath(getcwd().DIRECTORY_SEPARATOR.'lib');
		}
		if(class_exists('Composer\Autoload\ClassLoader')){
			$r = new \ReflectionClass('Composer\Autoload\ClassLoader');
				
			$vendor_dir = dirname(dirname($r->getFileName()));
			if(is_file($loader_php=$vendor_dir.DIRECTORY_SEPARATOR.'autoload.php')){
				$loader = include($loader_php);
	
				// vendor以外の定義されているパスを探す
				foreach(array_merge($loader->getPrefixes(),$loader->getPrefixesPsr4()) as $ns){
					foreach($ns as $path){
						$path = realpath($path);
						
						if(strpos($path,$vendor_dir) === false){
							$include_path[] = $path;
						}
					}
				}
			}
		}
		$include_path = array_unique($include_path);
		
		$valid_find_class_file = function($f){
			if(strpos($f->getPathname(),DIRECTORY_SEPARATOR.'.') === false
				&& strpos($f->getPathname(),DIRECTORY_SEPARATOR.'_') === false
				&& strpos($f->getPathname(),DIRECTORY_SEPARATOR.'cmd'.DIRECTORY_SEPARATOR) === false
				&& ctype_upper(substr($f->getFilename(),0,1))
				&& substr($f->getFilename(),-4) == '.php'
			){
				try{
					include_once($f->getPathname());
				}catch(\Exception $e){
				}
			}
		};
	
		foreach($include_path as $libdir){
			if($libdir !== '.' && is_dir($libdir)){
				foreach(\ebi\Util::ls($libdir,true) as $f){
					$valid_find_class_file($f);
				}
			}
		}
	
		/**
		 * @param string[] $vendor 利用するvendorのクラス
		 */
		$use_vendor = \ebi\Conf::gets('use_vendor');
		
		/**
		 * @param callback $callback 利用するvendorのクラス配列を返すメソッド
		 */
		$use_vendor_callback = \ebi\Conf::get('use_vendor_callback');
	
		if(!empty($use_vendor_callback)){
			$callback_result = call_user_func($use_vendor_callback);
			
			if(is_array($callback_result)){
				$use_vendor = array_merge($use_vendor,$callback_result);
			}
		}
		if(is_array($use_vendor)){
			foreach($use_vendor as $class){
				$find_package = false;
				
				if(substr($class,-1) == '*'){
					$class = substr($class,0,-1);
					$find_package = true;
				}
				if(class_exists($class)){
					if($find_package){
						$r = new \ReflectionClass($class);
						
						foreach(\ebi\Util::ls(dirname($r->getFileName()),true) as $f){
							$valid_find_class_file($f);
						}
					}
				}
			}
		}
		
		$valid_find_class = function($r,$parent_class){
			if(!$r->isInterface()
				&& (empty($parent_class) || is_subclass_of($r->getName(),$parent_class))
				&& $r->getFileName() !== false
				&& strpos($r->getName(),'_') === false
				&& strpos($r->getName(),'Composer') === false
				&& strpos($r->getName(),'cmdman') === false
				&& strpos($r->getName(),'testman') === false
			){
				return true;
			}
			return false;
		};
		foreach(get_declared_classes() as $class){
			if($valid_find_class($r=(new \ReflectionClass($class)),$parent_class)){
				yield ['filename'=>$r->getFileName(),'class'=>'\\'.$r->getName()];
			}
		}
	}

	/**
	 * \ebi\Dt\MockRequestを継承したクラス名を登録する
	 * $entryにはmock_flow_mappingsを通じて\ebi\Flow::app()のマッピングを行うエントリを指定する
	 */
	public static function add_mock(...$mock_class_names): void{
		foreach($mock_class_names as $class_name){
			if(is_object($class_name)){
				$class_name = get_class($class_name);
			}
			if(is_string($class_name)){
				if(!(class_exists($class_name) && is_subclass_of($class_name, \ebi\Dt\MockRequest::class))){
					throw new \InvalidArgumentException('invalid mock class: '.$class_name);
				}
				self::$mock[] = ltrim($class_name, '\\');
			}else if(is_array($class_name)){
				foreach($class_name as $c){
					self::add_mock($c);
				}
			}
		}
	}

	/**
	 * mock.phpの\ebi\Flow::app()に指定するマッピングを返す
	 */
	public static function mock_flow_mappings(array $map=[]): array{
		$patterns = $map['patterns'] ?? [];
		$patterns[''] = ['action'=>'ebi\Dt'];

		foreach(self::$mock as $class_name){
			$patterns[str_replace('\\', '/', $class_name)] = ['action'=>$class_name];
		}
		$map['patterns'] = $patterns;
		return $map;
	}

	private static function get_url_rewrite(): array{
		$patterns = \ebi\Conf::get('url_rewrite', []);

		$entry = \ebi\Conf::get('mock_entry_name', 'mock');
		foreach(self::$mock as $class_name){
			$inst = (new \ReflectionClass($class_name))->newInstance();
			foreach($inst->rewrite_map() as $pattern => $replacement){
				$patterns[$pattern] = $entry.'::'.str_replace('\\', '/', $class_name).(substr($replacement, 0, 1) == '/' ? $replacement : '/'.$replacement);
			}
		}
		return $patterns;
	}

	public static function url_rewrite(string $url): string{
		if(!\ebi\Conf::is_production()){
			$rewrite = self::get_url_rewrite();

			if(!empty($rewrite)){
				[$base_url, $query] = (strpos($url, '?') === false) ? [$url, ''] : explode('?', $url, 2);

				foreach($rewrite as $pattern => $replacement){
					$subject = (strpos($pattern, '\?') === false) ? $base_url : $url;

					if(!empty($pattern) && preg_match($pattern, $subject, $matches)){	
						$new_url_params = [];

						if(preg_match_all('/(\/%[0-9s]+)/', $replacement, $param_matches)){
							$match_params = array_slice($matches, 1);

							foreach($param_matches[0] as $i => $param_match){
								$idx = ($param_match == 's') ? $i : (int)substr($param_match, 2);
								$new_url_params[$idx] = $match_params[$idx] ?? '';

								$replacement = str_replace($param_match, '', $replacement);
							}
						}
						$new_url = preg_replace($pattern, $replacement, $subject);
						if(strpos($new_url, '?') !== false){
							[$new_url, $new_query] = explode('?', $new_url, 2);
							$query = $query.(empty($query) ? '' : '&').$new_query;
						}
						$new_url = self::url(empty($new_url_params) ? $new_url : array_merge([$new_url], $new_url_params));
						$new_url = $new_url.(empty($query) ? '' : ((strpos($new_url, '?') === false) ? '?' : '&').$query);
						\ebi\Log::debug('URL rewrite: '.$url.' to '.$new_url);

						return $new_url;
					}
				}
			}
		}
		return $url;
	}

	public static function url(string|array $url): string{
		if(!\ebi\Conf::is_production()){
			[$url, $params] = is_array($url) ? [$url[0], array_slice($url, 1)] : [$url, []];

			if(strpos($url, '://') === false){
				$map_urls = self::get_urls();

				if(!empty($map_urls) && isset($map_urls[$url]) && substr_count($map_urls[$url], '%s') == sizeof($params)){
					return vsprintf($map_urls[$url], $params);
				}
			}
		}
		return $url;
	}
	
	private static function get_urls(): array{
		$dir = getcwd();		
		$urls = [];
		foreach(new \RecursiveDirectoryIterator(
			$dir,
			\FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
		) as $f){
			if(substr($f->getFilename(),-4) == '.php' && !preg_match('/\/[\._]/',$f->getPathname())){
				$entry_name = substr($f->getFilename(),0,-4);
				$src = file_get_contents($f->getPathname());
	
				if(strpos($src,'Flow') !== false){
					$entry_name = substr($f->getFilename(),0,-4);
					$map = \ebi\Flow::get_map($f->getPathname());
					
					foreach($map['patterns'] as $m){
						$urls[$entry_name.'::'.$m['name']] = $m['format'];
					}
				}
			}
		}
		return $urls;
	}

	public static function testman_config(bool $autocommit=true): array{
		\ebi\Conf::set(\ebi\Db::class, 'autocommit', $autocommit);
 		
		return [
			'urls'=>self::get_urls(),
			'url_rewrite'=>self::get_url_rewrite(),	
			'ssl-verify'=>false,
			'log_debug_callback'=>'\\ebi\\Log::debug',
		];
	}

	/**
	 * SmtpBlackholeDaoから送信されたメールの一番新しいものを返す
	 */
	public static function find_mail(string $to, string $tcode='', string $keyword=''): \ebi\SmtpBlackholeDao{
		return \ebi\SmtpBlackholeDao::find_mail($to, $tcode, $keyword);
	}
	/**
	 * テーブルを削除後作成する
	 */
	public static function  reset_tables(): void{
		foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
			$class = \ebi\Util::get_class_name($class_info['class']);
			call_user_func([$class,'drop_table']);
			call_user_func([$class,'create_table']);
		}
	}
}
