<?php
namespace ebi;
/**
 * 定義情報を格納するクラス
 * @author tokushima
 */
class Conf{
	private static $value = ['ebi.Conf'=>[]];
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
	private static function get_defined_class_key($key){
		if(strpos($key,'@') === false){
			list(,,$d) = debug_backtrace(false);
				
			if(!isset($d['class'])){
				throw new \ebi\exception\BadMethodCallException('bad key');
			}
			$class = str_replace('\\','.',$d['class']);
			
			if($class[0] === '.'){
				$class = substr($class,1);
			}
			if(preg_match('/^(.+?\.[A-Z]\w*)/',$class,$m)){
				$class = $m[1];
			}
		}else{
			list($class,$key) = explode('@',$key,2);
		}
		return [$class,$key];
	}
	/**
	 * 定義情報を取得する
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($key,$default=null){
		list($class,$key) = self::get_defined_class_key($key);
		return self::exists($class,$key) ? self::$value[$class][$key] : $default;
	}

	/**
	 * 定義情報を配列で取得する
	 * @param string $key
	 * @param mixed $default
	 * @return array
	 */	
	public static function gets($key,$default=[],$return_vars=[]){
		list($class,$key) = self::get_defined_class_key($key);
		$result = self::exists($class,$key) ? self::$value[$class][$key] : $default;
		
		if(!empty($result) && !is_array($result)){
			$result = [$result];
		}
		if(empty($return_vars)){
			return $result;
		}
		$result_vars = [];
		
		foreach($return_vars as $var_name){
			$result_vars[] = isset($result[$var_name]) ? $result[$var_name] : null;
		}
		return $result_vars;
	}
	/**
	 * Pluginに遅延セットする
	 * @param string $class
	 * @param string $obj
	 */
	public static function set_class_plugin($class,$obj=null){
		if(is_array($class)){
			foreach($class as $c => $v){
				static::set_class_plugin($c,$v);
			}
		}else if(!empty($obj)){
			$class = str_replace('.','\\',$class);
				
			if($class[0] === '\\'){
				$class = substr($class,1);
			}
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
	
		if(isset(self::$plugins[$class])){
			$rtn = self::$plugins[$class];
			unset(self::$plugins[$class]);
		}
		return $rtn;
	}
	private static function get_self_conf_get($key,$d=null){
		return array_key_exists($key,self::$value['ebi.Conf']) ? self::$value['ebi.Conf'][$key] : $d;
	}
	
	
	
	/**
	 * アプリケーションの動作環境
	 * @return string
	 */
	public static function appmode(){
		return constant('APPMODE');
	}
	/**
	 * 現在のアプリケーションモードがモードに所属しているか
	 * @param string $mode アプリケーションモード
	 * @return  boolean
	 */
	public static function in_mode($mode){
		/**
		 * アプリケーションモードのグループ 
		 * 
		 * `````````````````````````
		 * [
		 * 	グループ名 => [モード,モード]
		 * ]
		 * `````````````````````````
		 * 
		 * @param array $group
		 */
		$group = self::get_self_conf_get('appmode_group',[]);		
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
		$dir = self::get_self_conf_get('work_dir');
		
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
		$dir = self::get_self_conf_get('resource_dir');
		
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
		 * 0 を指定すると "ブラウザを閉じるまで" という意味になります
		 * デフォルトは、0 です
		 * @param integer $val
		 */
		$cookie_lifetime = self::get_self_conf_get('cookie_lifetime',0);
		
		/**
		 * クッキーで設定するパス
		 * デフォルトは、/ です
		 * @param string $val
		 */
		$cookie_path = self::get_self_conf_get('cookie_path','/');
		
		/**
		 * クッキーで指定するドメイン
		 * @param string $val
		 */
		$cookie_domain = self::get_self_conf_get('cookie_domain');
		
		/**
		 *  セキュアな接続を通じてのみCookieを送信できるか
		 * デフォルトは、false です
		 * @param boolean $val
		 */
		$cookie_secure = self::get_self_conf_get('cookie_secure',false);
		
		/**
		 * セッション名
		 * デフォルトは、SID です
		 * @param string $val
		 */
		$session_name = self::get_self_conf_get('session_name','SID');
		
		/**
		 * キャッシュの有効期限 (分)
		 * デフォルトは、180 です
		 * @param integer $val
		 */
		$session_expire = self::get_self_conf_get('session_expire',180);
		
		/**
		 * キャッシュリミッタの名前 public / private_no_expire / private / nocache
		 * デフォルトは、nocache です
		 * @param string $val
		 * @see http://jp2.php.net/manual/ja/function.session-cache-limiter.php
		 */
		$session_limiter = self::get_self_conf_get('session_limiter','nocache');
		
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
		return self::get_self_conf_get('session_name','SID');
	}
	/**
	 * timestampの表現書式
	 * @return string 
	 */
	public static function timestamp_format(){
		/**
		 * timestamp型の書式
		 * @see http://php.net/manual/ja/function.date.php
		 */
		return self::get_self_conf_get('timestamp_format','c');
	}
	/**
	 * dateの表現書式
	 * @return string
	 */
	public static function date_format(){
		/**
		 * date型の書式
		 * @see http://php.net/manual/ja/function.date.php
		 */
		return self::get_self_conf_get('date_format','Y-m-d');
	}
}