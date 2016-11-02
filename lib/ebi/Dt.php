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
		];
	}
	public function get_after_vars(){
		$vars = [
			'f'=>new \ebi\Dt\Helper(),
			'appmode'=>constant('APPMODE')
		];
		return $vars;
	}
	/**
	 * @automap
	 */
	public function index(){
		$flow_output_maps = [];
	
		$map = \ebi\Flow::get_map($this->entry);
		$patterns = $map['patterns'];
		unset($map['patterns']);
	
		foreach($patterns as $k => $m){
			foreach([
				'deprecated'=>false,
				'mode'=>null,
				'summary'=>null,
				'template'=>null,
			] as $i => $d){
				if(!isset($m[$i])){
					$m[$i] = $d;
				}
			}
			if(isset($m['action']) && is_string($m['action'])){
				list($m['class'],$m['method']) = explode('::',$m['action']);
				
				if(substr($m['class'],0,1) == '\\'){
					$m['class'] = substr($m['class'],1);
				}
				$m['class'] = str_replace('\\','.',$m['class']);
			}
			if(!isset($m['class']) || $m['class'] != $this->self_class){
				try{
					$m['error'] = null;
					$m['url'] = $k;
	
					if(isset($m['method'])){
						$info = \ebi\Dt\Man::method_info($m['class'],$m['method'],false);

						if(empty($m['summary'])){
							list($summary) = explode(PHP_EOL,$info->document());
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
				$flow_output_maps[$m['name']] = $m;
			}
		}
		$entry_desc = (preg_match('/\/\*\*.+?\*\//s',\ebi\Util::file_read($this->entry),$m)) ?
			trim(preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$m[0]))) :
			'';
	
		return [
			'map_list'=>$flow_output_maps,
			'description'=>$entry_desc,
		];
	}	
	/**
	 * アクションのドキュメント
	 * @param string $name
	 * @context \ebi\man\DocInfo $method
	 * @automap
	 */
	public function action_doc($name){
		$map = \ebi\Flow::get_map($this->entry);
		
		foreach($map['patterns'] as $m){
			if($m['name'] == $name){
				list($m['class'],$m['method']) = explode('::',$m['action']);
				
				$info = \ebi\Dt\Man::method_info($m['class'],$m['method']);
				$info->set_opt('name',$name);
				$info->set_opt('url',$m['format']);
				
				foreach(['get_after_vars','get_after_vars_request'] as $mn){
					try{
						$ex_info = \ebi\Dt\Man::method_info($m['class'],$mn);
						
						foreach(['requests','contexts','args'] as $k){
							$info->set_opt($k,array_merge($ex_info->opt($k),$info->opt($k)));
						}
					}catch(\ReflectionException $e){
					}
				}
				return ['action'=>$info];
			}
		}
		throw new \ebi\exception\NotFoundException();
	}
	/**
	 * @automap
	 */
	public function config_list(){
		$conf_list = [];

		foreach(self::classes() as $info){
			$ref = new \ReflectionClass($info['class']);
			
			foreach(\ebi\Dt\Man::get_conf_list($ref) as $k => $c){
				$conf_list[$ref->getName()][] = $c;
			}
		}
		ksort($conf_list);
		
		return [
			'conf_list'=>$conf_list,
		];
	}
	
	/**
	 * クラスのドキュメント
	 * @param string $class
	 * @automap
	 */
	public function class_doc($class){
		$info = \ebi\Dt\Man::class_info($class);
		
		return [
			'class_info'=>$info,
		];
	}
	/**
	 * クラスドメソッドのドキュメント
	 * @param string $class
	 * @param string $method
	 * @automap
	 */
	public function method_doc($class,$method){
		$info = \ebi\Dt\Man::method_info($class,$method,true);
		
		return [
			'method_info'=>$info,
		];
	}
	
	/**
	 * @automap
	 */
	public function plugin_list(){
		$plugins_class = [];
		
		foreach(self::classes() as $class_info){

			$info = \ebi\Dt\Man::class_info($class_info['class']);
			
			if($info->has_opt('plugins')){
				$plugins_class[] = $info;
			}
		}
		return [
			'class_list'=>$plugins_class,
		];
	}
	
	/**
	 * @automap
	 */
	public function mail_list(){
		$template_list = \ebi\Dt\Man::mail_template_list();
		
		foreach(self::classes() as $class_info){
			$src = file_get_contents($class_info['filename']);
			
			foreach($template_list as $k => $info){
				if(strpos($src,$info->name()) !== false){
					$template_list[$k]->set_opt('use',true);
				}
			}
		}
		return [
			'template_list'=>$template_list,
		];
	}
	
	/**
	 * @automap
	 */
	public function document(){
		$req = new \ebi\Request();
		$file_list = [];
		$doc = null;
		
		$dir = \ebi\Conf::resource_path('documents/'.$this->entry_name);
		
		if(is_dir($dir)){
			$dir = realpath($dir);
			
			foreach(\ebi\Util::ls($dir,true,'/\.md$/') as $f){
				$name = substr(str_replace($dir,'',$f->getPathname()),1,-3);
				
				$fp = fopen($f->getPathname(),'r');
				$line = trim(fgets($fp,4096));
				fclose($fp);
				
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
		
	
	
	/**
	 * @automap
	 */
	public function model_list(){
		$model_list = [];
	
		foreach(self::classes('\ebi\Dao') as $class_info){
			$class = $class_info['class'];
			$r = new \ReflectionClass($class);
	
			if((!$r->isInterface() && !$r->isAbstract()) && is_subclass_of($class,'\ebi\Dao')){
				$class_doc = $r->getDocComment();
				$package = str_replace('\\','.',substr($class,1));
	
				$document = trim(preg_replace('/@.+/','',preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$class_doc))));
				list($summary) = explode("\n",$document);
	
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
		ksort($model_list);
		return ['models'=>$model_list];
	}
	
	/**
	 * 検索
	 *
	 * @param string $name モデル名
	 * @automap
	 *
	 * @request string $order ソート順
	 * @request int $page ページ番号
	 * @request string $porder 直前のソート順
	 *
	 * @context array $object_list 結果配列
	 * @context Paginator $paginator ページ情報
	 * @context string $porder 直前のソート順
	 * @context Dao $model 検索対象のモデルオブジェクト
	 * @context string $model_name 検索対象のモデルの名前
	 */
	public function do_find($class_name){
		$req = new \ebi\Request();
		$class_name = str_replace('/','\\',$class_name);
		$class_name = \ebi\Util::get_class_name($class_name);
		$paginator = \ebi\Paginator::request($req);
		
		$ref = new \ReflectionClass($class_name);
		$dao = $ref->newInstance();
		
		$object_list = call_user_func_array(
			[$dao,'find_all'],
			[$paginator]
		);
		return $req->ar_vars([
			'class_name'=>$class_name,
			'class_path'=>str_replace('\\','/',$class_name),
			'object_list'=>$object_list,
			'paginator'=>$paginator,
			'model'=>$dao,
		]);
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
