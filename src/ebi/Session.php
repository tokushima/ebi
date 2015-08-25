<?php
namespace ebi;
/**
 * セッションを操作する
 * @see http://jp2.php.net/manual/ja/function.session-set-save-handler.php
 * @author tokushima
 * @conf string $session_limiter キャッシュリミッタ nocache,private,private_no_expire,public
 * @conf integer $session_expire キャッシュの有効期限(sec)
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
			/**
			 * セッション名
			 */
			$session_name = \ebi\Conf::get('session_name','SID');
			
			if(!ctype_alpha($session_name)){
				throw new \InvalidArgumentException('session name is is not a alpha value');
			}
			/**
			 * キャッシュリミッタの名前
			 */
			session_cache_limiter(\ebi\Conf::get('session_limiter','nocache'));
			/**
			 * キャッシュの有効期限 (秒)、ただし設定は分に変換される
			 */
			session_cache_expire((int)(\ebi\Conf::get('session_expire',10800)/60));
			session_name();

			if(static::has_class_plugin('session_read')){
				ini_set('session.save_handler','user');
				session_set_save_handler(
					[$this,'open'],
					[$this,'close'],
					[$this,'read'],
					[$this,'write'],
					[$this,'destroy'],
					[$this,'gc']
				);
				if(isset($this->vars[$session_name])){
					session_regenerate_id(true);
				}
			}
			session_start();
			register_shutdown_function(function(){
				if('' != session_id()){
					session_write_close();
				}
			});
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
	 * セッションを開くときに実行される
	 * @param string $path
	 * @param string $name
	 * @return boolean
	 */
	public function open($path,$name){
		$bool = static::call_class_plugin_funcs('session_open',$path,$name);
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * writeが実行された後で実行される
	 * @return boolean
	 */
	public function close(){
		$bool = static::call_class_plugin_funcs('session_close');
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * セッションが開始したとき実行されます
	 * @param string $id
	 * @return mixed
	 */
	public function read($id){
		return static::call_class_plugin_funcs('session_read',$id);
	}
	
	/**
	 * セッションの保存や終了が必要となったときに実行されます
	 * @param string $id
	 * @param mixed $sess_data
	 * @return boolean
	 */
	public function write($id,$sess_data){
		$bool = static::call_class_plugin_funcs('session_write',$id,$sess_data);
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * セッションを破棄した場合に実行される
	 * @param string $id
	 * @return boolean
	 */
	public function destroy($id){
		$bool = static::call_class_plugin_funcs('session_destroy',$id);
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * ガベージコレクタ
	 * @param integer $maxlifetime session.gc_maxlifetime
	 * @return boolean
	 */
	public function gc($maxlifetime){
		$bool = static::call_class_plugin_funcs('session_gc',$maxlifetime);
		return (!is_bool($bool)) ? true : $bool;
	}
}