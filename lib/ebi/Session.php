<?php
namespace ebi;
/**
 * セッションを操作する
 * @see http://jp2.php.net/manual/ja/function.session-set-save-handler.php
 * @author tokushima
 */
class Session{
	use \ebi\Plugin;
	private $ses_n;

	/**
	 * セッションを開始する
	 * @param string $name
	 * @return $this
	 * 
	 */
	public function __construct($name='sess'){
		$this->ses_n = $name;
		
		if('' === session_id()){
			$cookie_params = \ebi\Conf::cookie_params();
			
			session_name($cookie_params['session_name']);
			
			if(!empty($cookie_params['session_sid_length'])){
				ini_set('session.sid_length',$cookie_params['session_sid_length']);
			}
			if($cookie_params['session_maxlifetime'] > 0){
				ini_set('session.gc_maxlifetime',$cookie_params['session_maxlifetime']);
			}
			if(
				$cookie_params['session_lifetime'] > 0 || 
				$cookie_params['cookie_path'] != '/' ||
				!empty($cookie_params['cookie_domain']) ||
				$cookie_params['cookie_secure'] !== false
			){
				session_set_cookie_params(
					$cookie_params['session_lifetime'],
					$cookie_params['cookie_path'],
					$cookie_params['cookie_domain'],
					$cookie_params['cookie_secure']
				);
			}
			
			if(static::has_class_plugin('session_read')){
				session_set_save_handler(
					[$this,'open'],
					[$this,'close'],
					[$this,'read'],
					[$this,'write'],
					[$this,'destroy'],
					[$this,'gc']
				);
			}
			session_start();
			
			register_shutdown_function(function(){
				if('' != session_id()){
					session_write_close();
				}
			});
			
			if(isset($this->vars[session_name()])){
				session_regenerate_id(true);
			}
		}
	}
	
	/**
	 * セッションの設定
	 * @param string $name
	 * @param mixed $value
	 */
	public function vars($key,$value){
		$_SESSION[$this->ses_n][$key] = $value;
	}
	
	/**
	 * セッションの取得
	 * @param string $n
	 * @param mixed $d 未定義の場合の値
	 * @return mixed
	 */
	public function in_vars($n,$d=null){
		return isset($_SESSION[$this->ses_n][$n]) ? $_SESSION[$this->ses_n][$n] : $d;
	}
	
	/**
	 * すべてのセッションの取得
	 * @return array
	 */
	public function ar_vars(){
		return isset($_SESSION[$this->ses_n]) ? $_SESSION[$this->ses_n] : [];
	}
	
	/**
	 * キーが存在するか
	 * @param string $n
	 * @return boolean
	 */
	public function is_vars($n){
		return isset($_SESSION[$this->ses_n]) ? array_key_exists($n,$_SESSION[$this->ses_n]) : false;
	}
	
	/**
	 * セッションを削除
	 */
	public function rm_vars(){
		foreach(((func_num_args() === 0) ? array_keys($_SESSION[$this->ses_n]) : func_get_args()) as $n) unset($_SESSION[$this->ses_n][$n]);
	}
	
	
	/**
	 * (session_set_save_handler) 初期処理
	 * @param string $path セッションを格納/取得するパス。
	 * @param string $name セッション名
	 * @return boolean
	 */
	public function open($path,$name){
		/**
		 * 初期処理
		 * @param string $path セッションを格納/取得するパス
		 * @param string $name セッション名
		 * @return boolean
		 */
		$bool = static::call_class_plugin_funcs('session_open',$path,$name);
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * (session_set_save_handler) writeが実行された後で実行される
	 * @return boolean
	 */
	public function close(){
		/**
		 * writeが実行された後で実行される
		 * @return boolean
		 */
		$bool = static::call_class_plugin_funcs('session_close');
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * (session_set_save_handler) データを読み込む
	 * @param string $id
	 * @return mixed
	 */
	public function read($id){
		/**
		 * データを読み込む
		 * @param string $id セッションのid
		 * @return mixed 読み込んだデータ
		 */
		return static::call_class_plugin_funcs('session_read',$id);
	}
	
	/**
	 * (session_set_save_handler) データを書き込む
	 * @param string $id セッションのid
	 * @param mixed $sess_data データ
	 * @return boolean
	 */
	public function write($id,$sess_data){
		/**
		 * データを書き込む
		 * @param string セッションのid
		 * @param mixed データ
		 * @return boolean
		 */
		$bool = static::call_class_plugin_funcs('session_write',$id,$sess_data);
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * (session_set_save_handler) 破棄
	 * @param string $id セッションのid
	 * @return boolean
	 */
	public function destroy($id){
		/**
		 * 破棄
		 * @param string セッションのid
		 * @return boolean
		 */
		$bool = static::call_class_plugin_funcs('session_destroy',$id);
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * (session_set_save_handler) 古いセッションを削除する
	 * @param integer $maxlifetime session.gc_maxlifetime
	 * @return boolean
	 */
	public function gc($maxlifetime){
		/**
		 * 古いセッションを削除する
		 * @param integer $maxlifetime session.gc_maxlifetime
		 * @return boolean
		 */
		$bool = static::call_class_plugin_funcs('session_gc',$maxlifetime);
		return (!is_bool($bool)) ? true : $bool;
	}
}
