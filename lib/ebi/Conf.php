<?php
namespace ebi;
/**
 * 定義情報を格納するクラス
 * @author tokushima
 */
class Conf{
	private static $value = [self::class=>[]];
	private static $plugins = [];
	
	private static function get_defined_class_key($key){
		if(strpos($key,'@') === false){
			list(,,$d) = debug_backtrace(false);
			
			if(!array_key_exists('class',$d)){
				throw new \ebi\exception\BadMethodCallException('is not allowed');
			}
			return [$d['class'],$key];
		}
		list($class_name,$key) = explode('@',$key,2);
		return [$class_name,$key];
	}
	
	/**
	 * 定義済みの定義名一覧
	 * @return string[]
	 */
	public static function get_defined_keys(){
		$rtn = [];
		foreach(self::$value as $c => $p){
			$rtn[$c] = array_keys($p);
		}
		return $rtn;
	}
	
	/**
	 * 定義情報をセットする
	 * @param string $class_name 
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($class_name,$key=null,$value=null){
		if(is_array($class_name)){
			foreach($class_name as $c => $v){
				foreach($v as $k => $value){
					if(!isset(self::$value[$c]) || !array_key_exists($k,self::$value[$c])){
						self::$value[$c][$k] = $value;
					}
				}
			}
		}else if(!empty($key)){
			if(func_num_args() > 3){
				$value = func_get_args();
				array_shift($value);
				array_shift($value);
			}
			if(!isset(self::$value[$class_name]) || !array_key_exists($key,self::$value[$class_name])){
				self::$value[$class_name][$key] = $value;
			}
		}
	}
	/**
	 * 定義されているか
	 * @param string $class_name
	 * @param string $key
	 * @return boolean
	 */
	public static function exists($class_name,$key){
		return (
			array_key_exists($class_name,self::$value) &&
			array_key_exists($key,self::$value[$class_name])
		);
	}

	/**
	 * 定義情報を取得する
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($key,$default=null){
		list($class_name,$key) = self::get_defined_class_key($key);
		return self::exists($class_name,$key) ? self::$value[$class_name][$key] : $default;
	}

	/**
	 * 定義情報を配列で取得する
	 * @param string $key
	 * @param mixed $default
	 * @return array
	 */	
	public static function gets($key,$default=[],$return_vars=[]){
		list($class_name,$key) = self::get_defined_class_key($key);
		$result = self::exists($class_name,$key) ? self::$value[$class_name][$key] : $default;
		
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
	 * @param string $class_name
	 * @param string[] $plugin_class_names
	 */
	public static function set_class_plugin($class_name,$plugin_class_names=null){
		if(is_array($class_name)){
			foreach($class_name as $c => $v){
				static::set_class_plugin($c,$v);
			}
		}else if(!empty($plugin_class_names)){
			if(!is_array($plugin_class_names)){
				$plugin_class_names = [$plugin_class_names];
			}
			foreach($plugin_class_names as $plugin_class_name){
				self::$plugins[$class_name][] = $plugin_class_name;
			}
		}
	}
	/**
	 * Pluginに遅延セットされたオブジェクトを返す
	 * @param string $class
	 * @return array
	 */
	public static function get_class_plugin($class_name){
		$rtn = [];
	
		if(isset(self::$plugins[$class_name])){
			$rtn = self::$plugins[$class_name];
			unset(self::$plugins[$class_name]);
		}
		return $rtn;
	}
	private static function get_self_conf_get($key,$d=null){
		return array_key_exists($key,self::$value[self::class]) ? 
			self::$value[self::class][$key] : 
			$d;
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
	 * @param string $mode アプリケーションモード、　グループを指定する場合は「@グループ名」
	 * @return  boolean
	 */
	public static function in_mode($mode){
		/**
		 * [ グループ名 => [モード,モード] ]
		 * 
		 * @param string{} $group アプリケーションモードのグループ 
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
		 * @param string $val ワーキングディレクトリ
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
	 * @param string $path リソースファイルのディレクトリパス
	 */
	public static function resource_path($path=null){
		/**
		 * @param string $val リソースファイルのディレクトリ
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
		 * @param integer $val ブラウザに送信するクッキーの有効期間(秒)
		 * 0 を指定すると "ブラウザを閉じるまで" という意味になります
		 * デフォルトは、0 です
		 */
		$cookie_lifetime = self::get_self_conf_get('cookie_lifetime',0);
		
		/**
		 * @param string $val クッキーで設定するパス
		 * デフォルトは、/ です
		 */
		$cookie_path = self::get_self_conf_get('cookie_path','/');
		
		/**
		 * @param string $val クッキーで指定するドメイン
		 */
		$cookie_domain = self::get_self_conf_get('cookie_domain');
		
		/**
		 * デフォルトは、false です
		 * @param boolean $val セキュアな接続を通じてのみCookieを送信できるか
		 */
		$cookie_secure = self::get_self_conf_get('cookie_secure',false);
		
		/**
		 * デフォルトは、NULL です
		 * @param strig $val クロスサイトリクエスト設定 ( Strict, Lax, None )
		 */
		$cookie_samesite = self::get_self_conf_get('cookie_samesite',null);
		
		
		/**
		 * デフォルトは、SID です
		 * @param string $val セッション名
		 */
		$session_name = self::get_self_conf_get('session_name','SID');
		
		/**
		 * @param integer $val ブラウザに送信するセッションIDの有効期間(秒)
		 * 0 を指定すると "ブラウザを閉じるまで" という意味になります
		 * デフォルトは、0 です
		 */
		$session_lifetime = self::get_self_conf_get('session_lifetime',0);
		
		/**
		 * 生存期間、デフォルトは1440です
		 * cookie_lifetimeが大きい場合、cookie_lifetimeで上書きされます
		 * セッション開始時にガベージコレクションが実行されるのでアプリが共存している場合は注意が必要です
		 * @param string $val 消去されるまでの秒数を指定します。
		 */
		$session_maxlifetime = self::get_self_conf_get('session_maxlifetime',1440);
		
		if($session_maxlifetime < $session_lifetime){
			$session_maxlifetime = $session_lifetime;
		}
		
		/**
		 * @param integer $val セッション ID 文字列の長さを指定します。 22 から 256 までの値が使えます。
		 */
		$session_sid_length = self::get_self_conf_get('session_sid_length');
		
		return [
			'session_name'=>$session_name,
			'session_sid_length'=>$session_sid_length,
			'session_maxlifetime'=>$session_maxlifetime,
			'session_lifetime'=>$session_lifetime,
			'cookie_lifetime'=>$cookie_lifetime,
			'cookie_path'=>$cookie_path,
			'cookie_domain'=>$cookie_domain,
			'cookie_secure'=>$cookie_secure,
			'cookie_samesite'=>$cookie_samesite,
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
		 * @param string $val Y-m-d H:i:s
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
		 * @param string $val Y-m-d
		 * @see http://php.net/manual/ja/function.date.php
		 */
		return self::get_self_conf_get('date_format','Y-m-d');
	}
		
	/**
	 * スクリプトが確保できる最大メモリを設定
	 * @param integer $mem memory size (MB)
	 */
	public static function memory_limit($mem){
		ini_set('memory_limit',($mem > 0) ? $mem.'M' : -1);
	}
}