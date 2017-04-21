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
			\ebi\Dt\TwitterBootstrap3Helper::class,
		];
	}
	public function get_after_vars(){
		$vars = [
			'f'=>new \ebi\Dt\Helper(),
			'appmode'=>constant('APPMODE'),
			'has_test'=>is_dir(self::test_path()),
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
				'deprecated'=>null,
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
				
				$info = \ebi\Dt\Man::method_info($m['class'],$m['method'],true);
				$info->set_opt('name',$name);
				$info->set_opt('url',$m['format']);
				
				if(!empty($info->opt('deprecated')) || isset($m['deprecated'])){
					if(isset($m['deprecated'])){
						$deprecated = is_bool($m['deprecated']) ? time() : strtotime($m['deprecated']);
					}else{
						$deprecated = $info->opt('deprecated');
					}
					$info->set_opt('deprecated',$deprecated);
				}
				foreach(['get_after_vars','get_after_vars_request'] as $mn){
					try{
						$ex_info = \ebi\Dt\Man::method_info($m['class'],$mn);
						
						foreach(['requests','contexts','args'] as $k){
							$info->set_opt($k,array_merge($ex_info->opt($k),$info->opt($k)));
						}
					}catch(\ReflectionException $e){
					}
				}
				$info->set_opt('test_list',self::test_file_list(basename($this->entry,'.php').'::'.$name));
				
				return ['action'=>$info];
			}
		}
		throw new \ebi\exception\NotFoundException();
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
	 * class list
	 * @automap
	 */
	public function class_list(){
		$req = new \ebi\Request();
		$parent = $req->in_vars('parent');
		$select = 'other';

		if(!empty($parent)){
			$parent = \ebi\Util::get_class_name($parent);

			switch($parent){
				case '\ebi\Dao':
					$select = 'model';
					break;
				case '\ebi\flow\Request':
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
	public function config_list(){
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
	 * Plugins
	 * @automap
	 */
	public function plugin_list(){
		$list = [];
		
		foreach(self::classes() as $class_info){
			$class_info = \ebi\Dt\Man::class_info($class_info['class']);
			
			if($class_info->has_opt('plugins')){
				$list[$class_info->name()] = $class_info;
			}
		}
		ksort($list);
		
		return [
			'class_info_list'=>$list,
		];
	}
	/**
	 * @automap
	 */
	public function plugin_doc($class,$plugin){
		$class_info = \ebi\Dt\Man::class_info($class);
		$plugins = $class_info->opt('plugins');

		if(!empty($plugins)){
			foreach($plugins as $p){
				if($p->name() == $plugin){
					return [
						'plugin_info'=>$p,
						'class_info'=>$class_info,
					];
				}
			}
		}
		throw new \ebi\exception\NotFoundException();
	}
	private function test_path(){
		/**
		 * @param string $val Test path root
		 */
		$testdir = \ebi\Conf::get('test_dir',getcwd().'/test/');
		
		return \ebi\Util::path_slash($testdir,null,true);
	}
	private function test_file_list($entry=null){
		$testdir = $this->test_path();
		$test_list = [];
		try{
			foreach(\ebi\Util::ls($testdir,true,'/\.php$/') as $f){
				if(
					strpos($f->getFilename(),'testman') === false &&
					strpos($f->getPathname(),'/_') === false
				){
					$name = str_replace($testdir,'',$f->getPathname());
					$src = file_get_contents($f->getPathname());
					
					if(empty($entry) || preg_match('/url\(([\'\"])'.preg_quote($entry,'/').'\\1/',$src)){
						$pos = strpos($src,'*/');
							
						if($pos === false){
							$info = new \ebi\Dt\DocInfo();
							$info->name($name);
						}else{
							$info = \ebi\Dt\DocInfo::parse($name,$src,$pos+2);
						}
						$short_name = substr($info->name(),0,-4);
						
						if(strlen($short_name) > 60){
							$short_name = substr($short_name,0,20).' ... '.substr($short_name,-40);
						}
						$info->set_opt('short_name',$short_name);
						$test_list[$info->name()] = $info;
					}
				}
			}
			ksort($test_list);
		}catch(\ebi\exception\InvalidArgumentException $e){
		}
		return $test_list;
	}
	/**
	 * @automap
	 */
	public function test_list(){
		$test_list = self::test_file_list();
		
		return [
			'test_list'=>$test_list,
		];
	}
	/**
	 * @automap
	 * @throws \ebi\exception\NotFoundException
	 */
	public function test_view(){
		$req = new \ebi\Request();
		$testdir = $this->test_path();
		$req_path = $req->in_vars('path');		
		$path = \ebi\Util::path_absolute($testdir,$req_path);
		
		if(strpos($path,$testdir) === false){
			throw new \ebi\exception\NotFoundException($req->in_vars('path').' not found');
		}
		$src = str_replace('<?php','',file_get_contents($path));
		
		$pos = strpos($src,'*/');
		
		if($pos === false){
			$info = new \ebi\Dt\DocInfo();
			$info->name($req_path);
		}else{
			$info = \ebi\Dt\DocInfo::parse($req_path,$src,$pos+2);
		}
		while($path != $testdir){
			$path = dirname($path).'/';
			
			if(is_file($f=$path.'__setup__.php')){
				$src = str_replace('<?php','',file_get_contents($f)).PHP_EOL.'// '.str_repeat('-',80).PHP_EOL.$src;
			}
		}
		return [
			'info'=>$info,
			'src'=>'<?php'.PHP_EOL.$src,
		];
	}
	
	/**
	 * Mail Templates
	 * @context \ebi\Dt\DocInfo[] $template_list
	 * @context boolean $is_test
	 * @automap
	 */
	public function mail_list(){
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
					}
				}
			}
		}
		return [
			'is_test'=>$has_bh,
			'template_list'=>$template_list,
		];
	}
	/**
	 * @automap
	 */
	public function mail_blackhole(){
		$req = new \ebi\Request();
		$paginator = \ebi\Paginator::request($req);
		$list = \ebi\SmtpBlackholeDao::find_all(
			Q::eq('tcode',$req->in_vars('tcode')),
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
	public function mail_info(){
		$req = new \ebi\Request();
		
		$mail_info = new \ebi\Dt\DocInfo();
		foreach(\ebi\Dt\Man::mail_template_list() as $info){
			if($info->opt('x_t_code') == $req->in_vars('tcode')){
				$mail_info = $info;
				
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
				$mail_info->set_opt('body',\ebi\Util::plain_text(PHP_EOL.$body_xml->value().PHP_EOL).$signature_text);
				
				try{
					$html_xml = $xml->find_get('html');
					$mail_info->set_opt('html',\ebi\Util::file_read(\ebi\Util::path_absolute($path,$html_xml->in_attr('src'))));
				}catch(\ebi\exception\NotFoundException $e){
				}
	
				$method_list = $method_mail_info_list = [];
				foreach(self::classes() as $class){
					if(strpos(\ebi\Util::file_read($class['filename']),$mail_info->name())){
						$ref_class = new \ReflectionClass($class['class']);
						
						foreach($ref_class->getMethods() as $ref_method){
							if(strpos(\ebi\Dt\Man::method_src($ref_method),$mail_info->name())){
								$method_info = \ebi\Dt\Man::method_info($ref_class->getName(),$ref_method->getName(),true);
								
								foreach($method_info->opt('mail_list') as $x_t_code => $method_mail_info){
									if($x_t_code == $mail_info->opt('x_t_code')){
										$method_list[] = $method_info;
										$method_mail_info_list[] = $method_mail_info;
										break;
									}
								}
							}
						}
					}
				}
				if(sizeof($method_mail_info_list) == 1){
					$mail_info->set_opt('description',$method_mail_info_list[0]->opt('description'));
					
					foreach($method_mail_info_list[0]->params() as $p){
						$mail_info->add_params($p);
					}
				}
				break;
			}
		}
		return $req->ar_vars([
			'mail_info'=>$mail_info,
			'method_list'=>$method_list,
			'multiple_method'=>(sizeof($method_list) > 1),
		]);
	}
	/**
	 * @automap
	 */
	public function mail_view(){
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
	 * @return array
	 */
	public static function classes($parent_class=null){
		$result = [];
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
	 * @param string $tcode
	 * @param string $keyword
	 * @return \ebi\SmtpBlackholeDao
	 */
	public static function find_mail($to,$tcode='',$keyword=''){
		$q = new Q();
		$q->add(Q::eq('to',$to));
		$q->add(Q::gte('create_date',time()-300));
		
		if(!empty($tcode)){
			$q->add(Q::eq('tcode',$tcode));
		}	
		foreach(\ebi\SmtpBlackholeDao::find($q,Q::order('-id')) as $mail){
			$value = $mail->subject().$mail->message();
				
			if(empty($keyword) || mb_strpos($value,$keyword) !== false){
				return $mail;
			}
		}
		throw new \ebi\exception\NotFoundException('mail not found');
	}
	/**
	 * テーブルを削除後作成する
	 */
	public static function  reset_tables(){
		foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
			$class = \ebi\Util::get_class_name($class_info['class']);
			call_user_func([$class,'drop_table']);
			call_user_func([$class,'create_table']);
		}
	}
}
