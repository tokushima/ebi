<?php
namespace ebi;
use \ebi\Q;
/**
 * 開発支援ツール
 * @author tokushima
 *
 */
class Dt{
	use \ebi\FlowPlugin;
	
	private $entry;
	private $entry_name;
	private $self_class;
	
	public function __construct($entryfile=null){
		if(empty($entryfile)){		
			$this->self_class = str_replace('\\','.',__CLASS__);
			$trace = debug_backtrace(false);
			krsort($trace);
			
			foreach($trace as $t){
				if(isset($t['class']) && $t['class'] == 'ebi\Flow'){
					$this->entry = $t['file'];
					$this->entry_name = basename($this->entry,'.php');
					break;
				}
			}
		}else{
			$entryfile = realpath($entryfile);
			$this->entry = $entryfile;
			$this->entry_name = basename($this->entry,'.php');			
		}
	}
	public function get_flow_plugins(){
		return [
			\ebi\flow\plugin\TwitterBootstrap3Helper::class,
			\ebi\Dt\HelperReplace::class,
		];
	}
	public function get_after_vars(){
		return [
			'f'=>new \ebi\Dt\Helper(),
			'appmode'=>constant('APPMODE')
		];
	}
	private function filter_query($query,$value){
		$bool = true;
		$value = strtolower($value);
		
		if(!empty($query)){
			foreach($query as $q){
				if(strpos($value,strtolower($q)) === false){
					$bool = false;
				}
			}
		}
		return $bool;
	}
	private function get_query(){
		$req = new \ebi\Request();
		$q = trim($req->in_vars('q'));
		$query = [];
		
		foreach((empty($q) ? [] : explode(' ',str_replace('　',' ',$q))) as $v){
			if(trim($v) != ''){
				$query[] = $v;
			}
		}
		return $query;
	}
	/**
	 * @automap
	 */
	public function phpinfo(){
		ob_start();
			phpinfo();
		$info = ob_get_clean();
		$info = \ebi\Xml::extract($info,'body')->escape(false)->value();
		$info = preg_replace('/<table .+>/','<table class="table table-striped table-bordered table-condensed">',$info);

		return [
			'phpinfo'=>$info
		];
	}
	/**
	 * @automap
	 */
	public function index(){
		$flow_output_maps = [];
		$query = $this->get_query();
		
		$map = \ebi\Flow::get_map($this->entry);
		$patterns = $map['patterns'];
		unset($map['patterns']);
		
		foreach($patterns as $k => $m){
			if(!isset($m['deprecated'])) $m['deprecated'] = false;
			if(!isset($m['mode'])) $m['mode'] = null;
			if(!isset($m['summary'])) $m['summary'] = null;
			if(!isset($m['template'])) $m['template'] = null;
	
			if(isset($m['action']) && is_string($m['action'])){
				list($m['class'],$m['method']) = explode('::',$m['action']);
				if(substr($m['class'],0,1) == '\\') $m['class'] = substr($m['class'],1);
				$m['class'] = str_replace('\\','.',$m['class']);
			}
			if(!isset($m['class']) || $m['class'] != $this->self_class){
				try{
					$m['error'] = null;
					$m['url'] = $k;
	
					if(isset($m['method'])){
						$info = \ebi\Dt\Man::method_info($m['class'],$m['method']);
							
						if(empty($m['summary'])){
							list($summary) = explode(PHP_EOL,$info['description']);
							$m['summary'] = empty($summary) ? null : $summary;
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
				if($this->filter_query($query,$m['name'].$m['url'].$m['summary'])){
					$flow_output_maps[$m['name']] = $m;
				}
			}
		}
		return [
			'map_list'=>$flow_output_maps,
			'q'=>implode(' ',$query),
			'description'=>\ebi\Dt\Man::entry_description($this->entry),
		];
	}

	private function class_list_summary($class,$query,&$libs){
		$r = new \ReflectionClass($class);
		
		$class_doc = $r->getDocComment();
		$document = trim(preg_replace("/@.+/",'',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$class_doc))));
		list($summary) = explode("\n",$document);
		$pkg = str_replace('/','.',str_replace('\\','/',substr($class,1)));
		
		if($this->valid_class_list($pkg)){
			if($this->filter_query($query,$class.$document.$pkg)){
				$libs[$pkg] = $summary;
			}
		}
	}
	private function valid_class_list($class){
		/**
		 * 一覧から除外するクラス名のパターン(正規表現)
		 * @param string[] $ignore
		 */
		$ignore_patterns = \ebi\Conf::gets('ignore');
		
		if(!empty($ignore_patterns)){
			foreach($ignore_patterns as $p){
				if(preg_match('/^'.$p.'/',$class)){
					return false;
				}
			}
		}
		return true;
	}
	/**
	 * ライブラリの一覧
	 * @automap
	 */
	public function class_list(){
		$query = $this->get_query();
		$libs = [];
					
		if(!empty($query)){
			$q = str_replace('.','\\',implode('',$query));
				
			if($q[0] != '\\'){
				$q = '\\'.$q;
			}
			if(class_exists($q)){
				$this->class_list_summary($q,$query,$libs);
			}
		}
		foreach(self::classes() as $info){
			$this->class_list_summary($info['class'],$query,$libs);
		}
		ksort($libs);
		return [
			'class_list'=>$libs,
			'q'=>implode(' ',$query),
		];
	}
	/**
	 * クラスのドキュメント
	 * @param string $class
	 * @automap
	 */
	public function class_doc($class){
		$info = \ebi\Dt\Man::class_info($class);
		return $info;
	}
	/**
	 * クラスドメソッドのドキュメント
	 * @param string $class
	 * @param string $method
	 * @automap
	 */
	public function method_doc($class,$method){
		$info = \ebi\Dt\Man::method_info($class,$method,true);
		return $info;
	}
	
	/**
	 * アクションのドキュメント
	 * @param string $class
	 * @param string $method
	 * @automap
	 */
	public function action_doc($name){
		$map = \ebi\Flow::get_map($this->entry);
		
		foreach($map['patterns'] as $m){
			if($m['name'] == $name){
				list($m['class'],$m['method']) = explode('::',$m['action']);
				
				$info = \ebi\Dt\Man::method_info($m['class'],$m['method'],true);
				$plugins = (isset($map['plugins']) ? $map['plugins']  : []);
				
				if(is_subclass_of($this->strtoclass($m['class']),\ebi\flow\Request::class)){
					$plugins = array_merge($plugins,isset($m['plugins']) ? $m['plugins']  : []);
				}
				foreach($plugins as $p){
					$p = $this->strtoclass($p);
					
					foreach(['get_after_vars','get_after_vars_request'] as $mn){
						try{
							$r = new \ReflectionMethod($p,$mn);
							
							if(preg_match_all("/@context\s+([^\s]+)\s+\\$(\w+)(.*)/",$r->getDocComment(),$c)){
								foreach($c[0] as $k => $v){
									$info['context'][$c[2][$k]][0] = $c[1][$k];
									$info['context'][$c[2][$k]][1] = (isset($c[3][$k]) ? $c[3][$k] : 'null');
								}
							}
						}catch(\ReflectionException $e){
						}
					}
				}
				return $info;
			}
		}
		throw new \ebi\exception\NotFoundException();
	}
	
	/**
	 * pluginのキュメント
	 * @param string $class
	 * @param string $plugin_name
	 * @automap
	 */
	public function plugin_doc($class,$plugin_name){
		$ref = \ebi\Dt\Man::class_info($class);
		
		if(!isset($ref['plugins'][$plugin_name])){
			throw new \ebi\exception\NotFoundException($plugin_name.' not found');
		}
		return [
			'package'=>$class,
			'plugin_name'=>$plugin_name,
			'description'=>$ref['plugins'][$plugin_name][0],
			'params'=>$ref['plugins'][$plugin_name][1],
			'return'=>$ref['plugins'][$plugin_name][2],
		];
	}
	/**
	 * Confのキュメント
	 * @param string $class
	 * @param string $conf_name
	 * @automap
	 */
	public function conf_doc($class,$conf_name){
		$ref = \ebi\Dt\Man::class_info($class);
	
		if(!isset($ref['conf_list'][$conf_name])){
			throw new \ebi\exception\NotFoundException($conf_name.' not found');
		}
		return [
			'package'=>$class,
			'conf_name'=>$conf_name,
			'description'=>$ref['conf_list'][$conf_name][0],
			'params'=>$ref['conf_list'][$conf_name][1],
		];
	}

	/**
	 * @automap
	 * @return multitype:multitype:multitype:unknown string
	 */
	public function config(){
		$query = $this->get_query();
		$conf_list = [];
		
		foreach(self::classes() as $info){
			$ref = new \ReflectionClass($info['class']);
			
			foreach(\ebi\Dt\Man::get_conf_list($ref) as $k => $c){
				$p = str_replace(['\\','/'],['/','.'],$ref->getName());
				
				if($this->valid_class_list($p)){
					if($this->filter_query($query,$p.$c[0])){
						$conf_list[$p.'::'.$k] = ['package'=>$p,'key'=>$k,'description'=>$c[0]];
					}
				}
			}
		}
		ksort($conf_list);
		
		return [
			'conf_list'=>$conf_list,
			'q'=>implode(' ',$query),
		];
	}
	
	/**
	 * @automap
	 */
	public function model_list(){
		$query = $this->get_query();
		$model_list = [];
		
		foreach(self::classes('\ebi\Dao') as $class_info){
			$class = $class_info['class'];
			$r = new \ReflectionClass($class);
				
			if((!$r->isInterface() && !$r->isAbstract()) && is_subclass_of($class,'\ebi\Dao')){
				$class_doc = $r->getDocComment();
				$package = str_replace('\\','.',substr($class,1));
				
				if($this->valid_class_list($package)){
					$document = trim(preg_replace('/@.+/','',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$class_doc))));
					list($summary) = explode("\n",$document);
					
					if($this->filter_query($query,$package.$document)){
						$model_list[$package] = [
							'label'=>$package,
							'error'=>null,
							'error_query'=>null,
							'con'=>true,
							'summary'=>$summary
						];
				
						try{
							\ebi\Dao::start_record();
								call_user_func([$class,'find_get']);
							\ebi\Dao::stop_record();
						}catch(\ebi\exception\NotFoundException $e){
						}catch(\ebi\exception\ConnectionException $e){
							$model_list[$package]['error'] = $e->getMessage();
							$model_list[$package]['con'] = false;
						}catch(\Exception $e){
							$model_list[$package]['error'] = $e->getMessage();
							$model_list[$package]['error_query'] = print_r(\ebi\Dao::stop_record(),true);
						}
					}
				}
			}
		}
		ksort($model_list);
		return ['models'=>$model_list,'q'=>implode(' ',$query)];	
	}
	
	/**
	 * @automap
	 */
	public function document(){
		$req = new \ebi\Request();
		$file_list = [];
		$doc = null;
		$query = $this->get_query();
		
		$dir = \ebi\Conf::resource_path('documents/'.$this->entry_name);
		
		if(is_dir($dir)){
			$dir = realpath($dir);
			
			foreach(\ebi\Util::ls($dir,true,'/\.md$/') as $f){
				$name = substr(str_replace($dir,'',$f->getPathname()),1,-3);
				$line = null;
				
				if(empty($query)){
					$fp = fopen($f->getPathname(),'r');
					$line = trim(fgets($fp,4096));
					fclose($fp);
				}else{
					$src = file_get_contents($f->getPathname());
					
					if($this->filter_query($query,$src)){
						list($line) = explode(PHP_EOL,$src,2);
					}
				}
				if(isset($line)){
					$title = (preg_match('/\#([^#].+)/',$line)) ? substr($line,1) : $name;				
					$file_list[$name] = $title;
				}
			}
		}
		if(is_file($f=\ebi\Util::path_absolute($dir,'index'))){
			$index_list = [];
			
			foreach(explode(PHP_EOL,file_get_contents($f)) as $index){
				$index = trim($index);
				
				if(!empty($index)){
					if(isset($file_list[$index])){
						$index_list[$index] = $file_list[$index];
						unset($file_list[$index]);
					}
				}
			}
			$file_list = array_merge($index_list,$file_list);
		}
		if(!$req->is_vars('name')){
			foreach($file_list as $n => $v){
				$req->vars('name',$n);
				break;
			}
		}
		if($req->is_vars('name')){
			$file = \ebi\Util::path_absolute($dir,$req->in_vars('name').'.md');
				
			if(is_file($file)){
				$doc = file_get_contents($file);
			}
		}
		return $req->ar_vars([
			'doc'=>$doc,
			'select_name'=>$req->in_vars('name'),
			'file_list'=>$file_list,
		]);
	}
	
	private function get_model($name,$sync=true){
		$req = new \ebi\Request();
		$r = new \ReflectionClass('\\'.str_replace('.','\\',$name));
		$obj = $r->newInstance();
		
		if(is_array($req->in_vars('primary'))){
			foreach($req->in_vars('primary') as $k => $v){
				$obj->{$k}($v);
			}
		}
		return ($sync) ? $obj->sync() : $obj;
	}
	/**
	 * 検索
	 *
	 * @param string $name モデル名
	 * @automap
	 *
	 * @request string $order ソート順
	 * @request int $page ページ番号
	 * @request string $query 検索文字列
	 * @request string $porder 直前のソート順
	 *
	 * @context array $object_list 結果配列
	 * @context Paginator $paginator ページ情報
	 * @context string $porder 直前のソート順
	 * @context Dao $model 検索対象のモデルオブジェクト
	 * @context string $model_name 検索対象のモデルの名前
	 */
	public function do_find($package){
		$req = new \ebi\Request();
		$class = '\\'.str_replace('.','\\',$package);
		$order = \ebi\Sorter::order($req->in_vars('order'),$req->in_vars('porder'));
	
		if(!class_exists($class)){
			throw new \ebi\exception\InvalidArgumentException($class.' not found');
		}
		if(empty($order)){
			$dao = new $class();
			
			foreach($dao->props() as $n => $v){
				if($dao->prop_anon($n,'primary')){
					$order = '-'.$n;
					break;
				}
			}
		}
		$object_list = [];
		$req->vars('order',$order);
		$paginator = \ebi\Paginator::request($req);
		
		$q = new Q();
		foreach($req->ar_vars() as $k => $v){
			if($v !== '' && strpos($k,'search_') === 0){
				list(,$type,$key) = explode('_',$k,3);
				switch($type){
					case 'timestamp':
					case 'date':
						list($fromto,$key) = explode('_',$key);
						$q->add(($fromto == 'to') ? Q::lte($key,$v) : Q::gte($key,$v));
						break;
					default:
						$q->add(Q::contains($key,$v));
				}
				$paginator->vars($k,$v);
			}
			$paginator->vars('search',true);
		}
		$object_list = $class::find_all($q,$paginator,Q::select_order($order,$req->in_vars('porder')));
		
		return $req->ar_vars([
			'object_list'=>$object_list,
			'paginator'=>$paginator,
			'model'=>new $class(),
			'package'=>$package,
		]);
	}
	/**
	 * 詳細
	 * @param string $package モデル名
	 * @automap
	 */
	public function do_detail($package){
		$obj = $this->get_model($package);
		
		return [
			'object'=>$obj,
			'model'=>$obj,
			'package'=>$package,
		];
	}
	/**
	 * 削除
	 * @param string $package モデル名
	 * @automap @['post_after'=>'']
	 */
	public function do_drop($package){
		$req = new \ebi\Request();
		if($req->is_post()){
			$this->get_model($package)->delete();
		}
	}
	/**
	 * 更新
	 * @param string $package モデル名
	 * @automap @['post_cond_after'=>['save_and_add_another'=>['do_create','@package'],'save'=>['do_find','@package']]]
	 */
	public function do_update($package){
		$result = [];
		$req = new \ebi\Request();
		
		if($req->is_post()){
			$obj = $this->get_model($package,false);
			$obj->set_props($req->ar_vars());
			$obj->save();

			$result[($req->is_vars('save_and_add_another') ? 'save_and_add_another' : 'save')] = true;
		}else{
			$obj = $this->get_model($package);
		}
		$result['model'] = $obj;
		$result['package'] = $package;
		
		return $result;
	}
	/**
	 * 作成
	 * @param string $package モデル名
	 * @automap @['post_cond_after'=>['save_and_add_another'=>['do_create','@package'],'save'=>['do_find','@package']]]
	 */
	public function do_create($package){
		$result = [];
		$req = new \ebi\Request();
		
		if($req->is_post()){
			$obj = $this->get_model($package,false);
			$obj->set_props($req->ar_vars());
			$obj->save();
			
			$result[($req->is_vars('save_and_add_another') ? 'save_and_add_another' : 'save')] = true;
		}else{
			$obj = $this->get_model($package,false);
		}
		$result['model'] = $obj;
		$result['package'] = $package;
		
		return $result;
	}
	public static function get_dao_connection($package){
		if(!is_object($package)){
			$r = new \ReflectionClass('\\'.str_replace('.','\\',$package));
			$package = $r->newInstance();
		}
		if(!is_subclass_of($package,'\ebi\Dao')){
			throw new \ebi\exception\InvalidArgumentException($package.' must be an \ebi\Dao');
		}
	
		$connections = \ebi\Dao::connections();
		$conf = explode("\\",get_class($package));
		
		while(!isset($connections[implode('.',$conf)]) && !empty($conf)){
			array_pop($conf);
		}
		if(empty($conf)){
			if(!isset($connections['*'])){
				throw new \ebi\exception\ConnectionException(get_class($package).' connection not found');
			}
			$conf = ['*'];
		}
		$conf = implode('.',$conf);
		foreach($connections as $k => $con){
			if($k == $conf){
				return $con;
			}
		}
	}
	/**
	 * SQLを実行する
	 * @param string $package
	 * @automap
	 */
	public function do_sql($package){
		$req = new \ebi\Request();
		$result_list = $keys = [];
		$sql = $req->in_vars('sql');
		$count = 0;

		$con = self::get_dao_connection($package);

		if($req->is_vars('create_sql')){
			$r = new \ReflectionClass('\\'.str_replace('.','\\',$package));
			$dao = $r->newInstance();
			$sql = $con->connector()->create_table_sql($dao);
			$req->rm_vars('create_sql');
			$req->vars('sql',$sql);
		}
		if($req->is_post() && !empty($sql)){
			$excute_sql = [];
			$sql = str_replace(['\\r\\n','\\r','\\n','\;'],["\n","\n","\n",'{SEMICOLON}'],$sql);
			
			foreach(explode(';',$sql) as $q){
				$q = trim(str_replace('{SEMICOLON}',';',$q));

				if(!empty($q)){
					$excute_sql[] = $q;
					$con->query($q);
				}
			}
			if(preg_match('/^(select|desc)\s.+/i',$q)){
				foreach($con as $v){
					if(empty($keys)){
						$keys = array_keys($v);
					}
					$result_list[] = $v;
					$count++;
						
					if($count >= 100){
						break;
					}
				}
			}
			$req->vars('excute_sql',implode(';'.PHP_EOL,$excute_sql));
		}
		$req->vars('result_keys',$keys);
		$req->vars('result_list',$result_list);
		$req->vars('package',$package);
		$req->vars('maximum',($count >= 100));
		
		return $req->ar_vars();
	}
	/**
	 * エントリのURL群
	 * @param string $dir
	 * @return array
	 */
	public static function get_urls($dir=null){
		if(empty($dir)){
			$dir = getcwd();
		}
		
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
	/**
	 * ライブラリ一覧
	 * composerの場合はcomposer.jsonで定義しているPSR-0のもののみ
	 * @return array
	 */
	public static function classes($parent_class=null,$ignore=true){
		$result = [];
		$include_path = [];
		$ignore_class = [];
		
		if(is_dir(getcwd().DIRECTORY_SEPARATOR.'lib')){
			$include_path[] = realpath(getcwd().DIRECTORY_SEPARATOR.'lib');
		}
		if(class_exists('Composer\Autoload\ClassLoader')){
			$r = new \ReflectionClass('Composer\Autoload\ClassLoader');
			
			$vendor_dir = dirname(dirname($r->getFileName()));
			if(is_file($loader_php=$vendor_dir.DIRECTORY_SEPARATOR.'autoload.php')){
				$loader = include($loader_php);
				
				// vendor以外の定義されているパスを探す
				foreach($loader->getPrefixes() as $ns){
					foreach($ns as $path){
						$path = realpath($path);
						
						if(strpos($path,$vendor_dir) === false){
							$include_path[] = $path;
						}
					}
				}
			}
		}
		
		$valid_find_class_file = function($f){
			if(strpos($f->getPathname(),DIRECTORY_SEPARATOR.'.') === false
				&& strpos($f->getPathname(),DIRECTORY_SEPARATOR.'_') === false
				&& strpos($f->getPathname(),DIRECTORY_SEPARATOR.'cmd'.DIRECTORY_SEPARATOR) === false
				&& ctype_upper(substr($f->getFilename(),0,1))
				&& substr($f->getFilename(),-4) == '.php'
			){
				try{
					include_once($f->getPathname());
				}catch(\Exeption $e){
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
		 * 利用するvendorのクラス
		 * @param string[] $vendor
		 */
		$use_vendor = \ebi\Conf::gets('use_vendor');
		/**
		 * 利用するvendorのクラス配列を返すメソッド
		 * @param callback $callback
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
				
				$class = str_replace('.','\\',$class);
				
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
	 * モデルからtableを作成する
	 * @param boolean $drop
	 * @reutrn array 処理されたモデル
	 */
	public static function create_table($drop=false){
		$model_list = [];
		$result = [];
	
		foreach(self::classes('\ebi\Dao') as $class_info){
			$r = new \ReflectionClass($class_info['class']);
			
			if(($r->getParentClass() instanceof \ReflectionClass) && $r->getParentClass()->getName() == 'ebi\Dao'){
				$model_list[] = $class_info['class'];
			}
		}
		foreach($model_list as $class){
			$r = new \ReflectionClass($class);
			
			if($drop && call_user_func([$class,'drop_table'])){
				$result[] = [-1,$class];
			}
			if(call_user_func([$class,'create_table'])){
				$result[] = [1,$class];
			}
		}
		return $result;
	}
	/**
	 * モデルからデータを全削除する
	 */
	public static function delete_all(){		
		foreach(self::classes('\ebi\Dao') as $class_info){
			$r = new \ReflectionClass($class_info['class']);
	
			if(($r->getParentClass() instanceof \ReflectionClass) && $r->getParentClass()->getName() == 'ebi\Dao'){
				call_user_func([$r->getName(),'find_delete']);
			}
		}
	}
	/**
	 * dumpファイルを読み込む
	 * @param string $file
	 * @return Generator
	 */
	public static function get_dao_dump($file){
		$update = $invalid = [];
		$fp = fopen($file,'rb');
		
		$i = 0;
		$line = '';
		
		while(!feof($fp)){
			$i++;
			$line .= fgets($fp);
		
			if(!empty($line)){
				$arr = json_decode($line,true);
		
				if($arr !== false){
					if(!isset($arr['model']) || !isset($arr['data'])){
						throw new \ebi\exception\InvalidArgumentException('Invalid line '.$i);
					}
					yield $arr;

					$line = '';
				}
			}
		}		
	}
	/**
	 * dao dumpをexportする
	 * @param string $file
	 * @return integer{}
	 */
	public static function export_dao_dump($file){
		$export_result = [];
		
		\ebi\Util::file_write($file,'');
		
		foreach(self::classes('\ebi\Dao') as $class_info){
			$r = new \ReflectionClass($class_info['class']);
			$cnt = 0;
		
			if($r->getParentClass()->getName() == 'ebi\Dao'){
				foreach(call_user_func([$r->getName(),'find']) as $obj){
					\ebi\Util::file_append($file,json_encode(['model'=>$r->getName(),'data'=>$obj->props()]).PHP_EOL);
					$cnt++;
				}
			}
			if(!empty($cnt)){
				$export_result[$r->getName()] = $cnt;
			}
		}
		return $export_result;				
	}
	/**
	 * dao dumpをimportする
	 * @param string $dump_file
	 * @return mixed{}
	 */
	public static function import_dao_dump($dump_file){
		$update = $invalid = [];
		
		foreach(self::get_dao_dump($dump_file) as $arr){
			$class = $arr['model'];
			
			if(!isset($invalid[$class])){
				$inst = (new \ReflectionClass($class))->newInstance();
		
				if(!isset($update[$class])){
					$update[$class] = [call_user_func([$class,'find_count']),0];
				}
				try{
					foreach($inst->props() as $k => $v){
						if(array_key_exists($k,$arr['data'])){
							if($inst->prop_anon($k,'cond') == null && $inst->prop_anon($k,'extra',false) === false){
								$inst->prop_anon($k,'auto_now',false,true);
								call_user_func_array([$inst,$k],[$arr['data'][$k]]);
							}
						}
					}
					$inst->save();
					$update[$class][1]++;
				}catch(\ebi\exception\BadMethodCallException $e){
					$invalid[$class] = true;
				}
			}
		}
		return ['update'=>$update,'invalid'=>$invalid];
	}
	
	/**
	 * SmtpBlackholeDaoから送信されたメールの一番新しいものを返す
	 * @param string $to
	 * @param string $subject
	 * @param number $late_time sec
	 * @return \ebi\SmtpBlackholeDao
	 */
	public static function find_mail($to,$keyword=null,$late_time=60){
		if(empty($to)){
			throw new \ebi\exception\NotFoundException('`to` not found');
		}
	
		$q = new Q();
		$q->add(Q::eq('to',$to));
		$q->add(Q::gte('create_date',time()-$late_time));
		if(!empty($subject)) $q->add(Q::contains('subject',$subject));
	
		foreach(\ebi\SmtpBlackholeDao::find($q,Q::order('-id')) as $mail){
			$value = $mail->subject().$mail->message();
				
			if(empty($keyword) || mb_strpos($value,$keyword) !== false){
				return $mail;
			}
		}
		throw new \ebi\exception\NotFoundException('指定のメールが飛んでいない > ['.$to.'] '.$keyword);
	}
	
	/**
	 * アプリケーションモードに従い初期処理を行うファイルのパス
	 * @return string
	 */
	public static function setup_file(){
		$dir = defined('COMMONDIR') ? dirname(constant('COMMONDIR')) : getcwd();
		return $dir.'/setup/'.constant('APPMODE').'.php';
	}
	/**
	 * アプリケーションモードに従い初期処理を実行する
	 * setup/[APPMODE].phpの実行
	 */
	public static function setup(){
		if(is_file($f=self::setup_file())){
			include($f);
			return true;
		}
		return false;
	}
	private function strtoclass($str){
		$str = str_replace('.','\\',$str);
		
		if($str[0] != '\\'){
			$str = '\\'.$str;
		}
		if(class_exists($str)){
			return $str;
		}
		throw new \ebi\exception\NotFoundException();
	}
}
