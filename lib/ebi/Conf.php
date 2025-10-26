<?php
namespace ebi;

class Conf{
	private static array $value = [self::class=>[]];
	private static array $handler_obj = [];
	
	private static function get_defined_class_key(string $key): array{
		if(strpos($key,'@') === false){
			$trace = debug_backtrace(false);

			if(!array_key_exists('class', $trace[2] ?? [])){
				throw new \ebi\exception\BadMethodCallException('is not allowed');
			}
			return [$trace[2]['class'] ?? [], $key];
		}
		[$class_name, $key] = explode('@',$key,2);
		return [$class_name, $key];
	}
	
	/**
	 * 定義済みの定義名一覧
	 */
	public static function get_defined_keys(): array{
		$rtn = [];
		foreach(self::$value as $c => $p){
			$rtn[$c] = array_keys($p);
		}
		return $rtn;
	}
	
	/**
	 * 定義情報をセットする
	 * @param mixed $class_name (string|array)
	 * @param mixed $value
	 */
	public static function set($class_name, ?string $key=null, $value=null): void{
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

	private static function exists(string $class_name, string $key): bool{
		return (
			array_key_exists($class_name,self::$value) &&
			array_key_exists($key,self::$value[$class_name])
		);
	}

	/**
	 * 定義されている
	 */
	public static function defined(string $key): bool{
		[$class_name, $key] = self::get_defined_class_key($key);
		return self::exists($class_name,$key);
	}

	/**
	 * 定義情報を取得する
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get(string $key, $default=null){
		[$class_name, $key] = self::get_defined_class_key($key);
		return self::exists($class_name,$key) ? self::$value[$class_name][$key] : $default;
	}

	/**
	 * 定義情報を配列で取得する
	 */	
	public static function gets(string $key, array $default=[], array $return_vars=[]): array{
		[$class_name, $key] = self::get_defined_class_key($key);
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
	 * @param mixed $default
	 * @return mixed
	 */
	private static function get_self_conf_get(string $key, $default=null){
		return array_key_exists($key,self::$value[self::class]) ? 
			self::$value[self::class][$key] : 
			$default;
	}
	
	/**
	 * アプリケーションの動作環境
	 */
	public static function appmode(): string{
		if(defined('APPMODE')){
			return (string)constant('APPMODE');
		}
		return '';
	}

	public static function is_production(): bool{
		return (strpos(self::appmode(), 'production') !== false);
	}
	public static function is_local(): bool{
		return (strpos(self::appmode(),'local') !== false);
	}


	/**
	 * 現在のアプリケーションモードがモードに所属しているか
	 * アプリケーションモード、　グループを指定する場合は「@グループ名」
	 */
	public static function in_mode(string $mode): bool{
		/**
		 * [ グループ名 => [モード,モード] ]
		 * 
		 * @param string{} $group アプリケーションモードのグループ 
		 */
		$group = self::get_self_conf_get('appmode_group',[]);

		foreach((is_array($mode) ? 
			$mode : 
			((strpos($mode, ',') === false) ? [$mode] : explode(',', $mode))
		) as $m){
			if(substr($m,0,1) == '@'){
				$mode = substr($mode, 1);
				
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
	 */
	public static function work_path(?string $path=null): string{
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
	 * リソースファイルのパス
	 */
	public static function resource_path(?string $path=null): string{
		/**
		 * @param string $val リソースファイルのディレクトリ
		 */
		$dir = self::get_self_conf_get('resource_dir', '');
		
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
	 */
	public static function cookie_params(): array{
		/**
		 * @param int $val ブラウザに送信するクッキーの有効期間(秒)
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
		 * @param bool $val セキュアな接続を通じてのみCookieを送信できるか
		 */
		$cookie_secure = self::get_self_conf_get('cookie_secure',false);
		
		/**
		 * デフォルトは、空 です
		 * @param strig $val クロスサイトリクエスト設定 ( Strict, Lax, None )
		 */
		$cookie_samesite = self::get_self_conf_get('cookie_samesite','');
		
		/**
		 * デフォルトは、SID です
		 * @param string $val セッション名
		 */
		$session_name = self::get_self_conf_get('session_name','SID');
		
		/**
		 * @param int $val ブラウザに送信するセッションIDの有効期間(秒)
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
		 * @param int $val セッション ID 文字列の長さを指定します。 22 から 256 までの値が使えます。
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
	 */
	public static function session_name(): string{
		return self::get_self_conf_get('session_name','SID');
	}

	/**
	 * timestampの表現書式
	 */
	public static function timestamp_format(): string{
		/**
		 * timestamp型の書式
		 * @param string $val Y-m-d H:i:s
		 * @see http://php.net/manual/ja/function.date.php
		 */
		return self::get_self_conf_get('timestamp_format','c');
	}

	/**
	 * dateの表現書式
	 */
	public static function date_format(): string{
		/**
		 * date型の書式
		 * @param string $val Y-m-d
		 * @see http://php.net/manual/ja/function.date.php
		 */
		return self::get_self_conf_get('date_format','Y-m-d');
	}
		
	/**
	 * スクリプトが確保できる最大メモリ(MB)を設定
	 */
	public static function memory_limit(int $memory_limit_size): void{
		ini_set('memory_limit',($memory_limit_size > 0) ? $memory_limit_size.'M' : -1);
	}

	public static function defined_handler(): bool{
		[$class_name, $key] = self::get_defined_class_key('handler');
		return self::exists($class_name,$key);
	}

	public static function handle(string $method, ...$args){
		[$name, $key] = self::get_defined_class_key('handler');
		$handler_class = self::exists($name, $key) ? self::$value[$name][$key] : '';

		if(!empty($handler_class)){
			if(!isset(self::$handler_obj[$name])){
				self::$handler_obj[$name] = (new \ReflectionClass($handler_class))->newInstance();

				$interface = $name.'Handler';
				if(!is_subclass_of(self::$handler_obj[$name], $interface)){
					throw new \ebi\exception\NotImplementedException('does not implement: '.$interface);
				}
			}
			if(!method_exists(self::$handler_obj[$name], $method)){
				throw new \ebi\exception\BadMethodCallException(sprintf('does not implement: %s::%s', get_class(self::$handler_obj[$name]), $method));
			}
			return call_user_func_array([self::$handler_obj[$name], $method], $args);
		}
		return;
	}
}