<?php
namespace ebi;
/**
 * 定義情報を格納するクラス
 * @author tokushima
 */
class Conf{
	private static $value = [];
	private static $plugins = [];
	/**
	 * 定義情報をセットする
	 * @param string|array $class
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($class,$key=null,$value=null){
		if(is_array($class)){
			foreach($class as $c => $v){
				foreach($v as $k => $value){
					if(!isset(self::$value[$c]) || !array_key_exists($k,self::$value[$c])){
						self::$value[$c][$k] = $value;
					}
				}
			}
		}else if(!empty($key)){
			$class = str_replace("\\",'.',$class);
			if($class[0] === '.'){
				$class = substr($class,1);
			}
			if(func_num_args() > 3){
				$value = func_get_args();
				array_shift($value);
				array_shift($value);
			}
			if(!isset(self::$value[$class]) || !array_key_exists($key,self::$value[$class])){
				self::$value[$class][$key] = $value;
			}
		}
	}
	/**
	 * 定義されているか
	 * @param string $class
	 * @param string $key
	 * @return boolean
	 */
	public static function exists($class,$key){
		return (isset(self::$value[$class]) && array_key_exists($key,self::$value[$class]));
	}
	/**
	 * 定義情報を取得する
	 * @param string $key
	 * @param mixed $default
	 */
	public static function get($key,$default=null,$return_vars=null){
		if(strpos($key,'@') === false){
			list(,$d) = debug_backtrace(false);
			if(!isset($d['class'])){
				throw new \ebi\exception\BadMethodCallException('bad key');
			}
			$class = str_replace('\\','.',$d['class']);
			if($class[0] === '.') $class = substr($class,1);
			if(preg_match('/^(.+?\.[A-Z]\w*)/',$class,$m)) $class = $m[1];
		}else{
			list($class,$key) = explode('@',$key,2);
		}
		$result = self::exists($class,$key) ? self::$value[$class][$key] : $default;
		if(is_array($return_vars)){
			if(empty($return_vars) && !is_array($result)){
				return [$result];
			}
			$result_vars = [];
			foreach($return_vars as $var_name){
				$result_vars[] = isset($result[$var_name]) ? $result[$var_name] : null;
			}
			return $result_vars;
		}
		return $result;
	}
	/**
	 * アプリケーションの動作環境
	 * @return string
	 */
	public static function appmode(){
		return defined('APPMODE') ? constant('APPMODE') : null;
	}
	/**
	 * 現在のアプリケーションモードがモードに所属しているか
	 * @param string $mode アプリケーションモード
	 * @return  boolean
	 */
	public static function in_mode($mode){
		/**
		 * TODO test
		 * ## アプリケーションモードのグループ 
		 * 
		 * `````````````````````````
		 * [
		 * 	グループ名 => [モード,モード]
		 * ]
		 * `````````````````````````
		 * 
		 * @param array $group
		 */
		$group = \ebi\Conf::get('appmode_group',['dev'=>['local']]);
		$chkmode = is_array($mode) ? 
						$mode : 
						((strpos($mode,',') === false) ? [$mode] : explode(',',$mode));
		
		foreach($chkmode as $m){
			if(substr($m,0,1) == '@'){
				$mode = substr($mode,1);
				
				if(array_key_exists($mode,$group) && in_array(\ebi\Conf::appmode(),$group[$mode])){
					return true;
				}				
			}else if($m == self::appmode()){
				return true;
			}
		}
		return false;
	}
	/**
	 * 作業ディレクトリのパス
	 * @param string $path
	 * @return string
	 */
	public static function work_path($path=null){
		/**
		 * ワーキングディレクトリ
		 */
		$dir = \ebi\Conf::get('work_dir');
		
		if(empty($dir)){
			$dir = defined('WORK_DIR') ? constant('WORK_DIR') : (getcwd().'/work/');
		}
		$dir = str_replace("\\",'/',$dir);
		if(substr($dir,-1) != '/'){
			$dir = $dir.'/';
		}
		return $dir.$path;
	}
	/**
	 * リソースファイルのディレクトリパス
	 * @param string $path
	 * @return string
	 */
	public static function resource_path($path=null){
		/**
		 * リソースファイルのディレクトリ
		 */
		$dir = \ebi\Conf::get('resource_dir');
		
		if(empty($dir)){
			$dir = defined('RESOURCE_DIR') ? constant('RESOURCE_DIR') : (getcwd().'/resources/');
		}
		$dir = str_replace("\\",'/',$dir);
		if(substr($dir,-1) != '/'){
			$dir = $dir.'/';
		}
		return $dir.$path;
	}
	
	/**
	 * セッション・クッキーの定義
	 * @return mixed{}
	 */
	public static function cookie_params(){
		/**
		 * ブラウザに送信するクッキーの有効期間(秒)
		 * @param integer $val
		 */
		$cookie_lifetime = \ebi\Conf::get('cookie_lifetime',0);
		
		/**
		 * クッキーで設定するパス
		 * @param string $val
		 */
		$cookie_path = \ebi\Conf::get('cookie_path','/');
		
		/**
		 * クッキーで指定するドメイン
		 * @param string $val
		 */
		$cookie_domain = \ebi\Conf::get('cookie_domain');
		
		/**
		 *  セキュアな接続を通じてのみCookieを送信できるか
		 * @param boolean $val
		 */
		$cookie_secure = \ebi\Conf::get('cookie_secure',false);
		
		/**
		 * セッション名
		 * @param string $val
		 */
		$session_name = \ebi\Conf::get('session_name','SID');
		
		/**
		 * キャッシュの有効期限 (分)
		 * @param integer $val
		 */
		$session_expire = \ebi\Conf::get('session_expire',180);
		/**
		 * キャッシュリミッタの名前 public / private_no_expire / private / nocache
		 * @param string $val
		 * @see http://jp2.php.net/manual/ja/function.session-cache-limiter.php
		 */
		$session_limiter = \ebi\Conf::get('session_limiter','nocache');
		
		return [
			'session_name'=>$session_name,
			'session_expire'=>$session_expire,
			'session_limiter'=>$session_limiter,
			'cookie_lifetime'=>$cookie_lifetime,
			'cookie_path'=>$cookie_path,
			'cookie_domain'=>$cookie_domain,
			'cookie_secure'=>$cookie_secure,
		];
	}
	/**
	 * セッション名の定義
	 * @return string
	 */
	public static function session_name(){
		return \ebi\Conf::get('session_name','SID');
	}
	/**
	 * Pluginに遅延セットする
	 * @param string $class
	 * @param string $obj
	 */
	public static function set_class_plugin($class,$obj=null){
		if(is_array($class)){
			foreach($class as $c => $v){
				$c = str_replace("\\",'.',$c);
				if($c[0] === '.') $c = substr($c,1);				
				
				foreach($v as $k => $value){
					if(!isset(self::$plugins[$c]) || !array_key_exists($k,self::$plugins[$c])){
						self::$plugins[$c][] = $value;
					}
				}
			}
		}else if(!empty($obj)){
			$class = str_replace("\\",'.',$class);
			if($class[0] === '.') $class = substr($class,1);
			if(!is_array($obj)){
				$obj = [$obj];
			}
			foreach($obj as $o){
				self::$plugins[$class][] = $o;
			}
		}
	}
	/**
	 * Pluginに遅延セットされたオブジェクトを返す
	 * @param string $class
	 * @return array
	 */
	public static function get_class_plugin($class){
		$rtn = [];
		$class = str_replace('\\','.',$class);
		
		if(isset(self::$plugins[$class])){
			$rtn = self::$plugins[$class];
			unset(self::$plugins[$class]);
		}
		return $rtn;
	}
}