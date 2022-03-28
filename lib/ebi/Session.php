<?php
namespace ebi;
/**
 * @see http://jp2.php.net/manual/ja/function.session-set-save-handler.php
 */
class Session{
	private $ses_n;

	/**
	 * セッションを開始する
	 */
	public function __construct(string $name='sess'){
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
				$cookie_params['cookie_secure'] !== false ||
				$cookie_params['cookie_samesite'] !== '' ||
				$cookie_params['cookie_path'] !== '/' ||
				!empty($cookie_params['cookie_domain'])
			){
				$opt = [
					'lifetime'=>$cookie_params['session_lifetime'],
					'path'=>$cookie_params['cookie_path'],
					'domain'=>$cookie_params['cookie_domain'],
					'secure'=>$cookie_params['cookie_secure'],
				];
				if(!empty($cookie_params['cookie_samesite'])){
					$opt['samesite'] = $cookie_params['cookie_samesite'];
				}
				session_set_cookie_params($opt);
			}
			
			if(!empty(\ebi\Conf::get('handler'))){
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
	 * @param mixed $value
	 */
	public function vars(string $key, $value): void{
		$_SESSION[$this->ses_n][$key] = $value;
	}
	
	/**
	 * セッションの取得
	 * @return mixed
	 */
	public function in_vars(string $key, $default=null){
		return $_SESSION[$this->ses_n][$key] ?? $default;
	}
	
	/**
	 * すべてのセッションの取得
	 */
	public function ar_vars(): array{
		return $_SESSION[$this->ses_n] ?? [];
	}
	
	/**
	 * キーが存在するか
	 * @param string $n
	 * @return bool
	 */
	public function is_vars(string $key): bool{
		return array_key_exists($key, $_SESSION[$this->ses_n] ?? []);
	}
	
	/**
	 * セッションを削除
	 */
	public function rm_vars(...$args): void{
		if(empty($args)){
			$_SESSION[$this->ses_n] = [];
		}else{
			foreach($args as $key){
				unset($_SESSION[$this->ses_n][$key]);
			}
		}
	}
		
	/**
	 * (session_set_save_handler) 初期処理
	 * @param $path セッションを格納/取得するパス。
	 * @param $name セッション名
	 */
	public function open(string $path, string $name): bool{
		$bool = \ebi\Conf::call('handler', '\ebi\SessionHandler', 'session_open', $path, $name);
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * (session_set_save_handler) writeが実行された後で実行される
	 */
	public function close(): bool{
		$bool = \ebi\Conf::call('handler', '\ebi\SessionHandler', 'session_close');
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * (session_set_save_handler) データを読み込む
	 * @return mixed
	 */
	public function read(string $id){
		return \ebi\Conf::call('handler', '\ebi\SessionHandler', 'session_read', $id);
	}
	
	/**
	 * (session_set_save_handler) データを書き込む
	 */
	public function write(string $id, $sess_data): bool{
		$bool = \ebi\Conf::call('handler', '\ebi\SessionHandler', 'session_write', $id, $sess_data);
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * (session_set_save_handler) 破棄
	 */
	public function destroy(string $id): bool{
		$bool = \ebi\Conf::call('handler', '\ebi\SessionHandler', 'session_destroy', $id);
		return (!is_bool($bool)) ? true : $bool;
	}
	
	/**
	 * (session_set_save_handler) 古いセッションを削除する
	 * @param $maxlifetime session.gc_maxlifetime
	 */
	public function gc(int $maxlifetime): bool{
		$bool = \ebi\Conf::call('handler', '\ebi\SessionHandler', 'session_gc', $maxlifetime);
		return (!is_bool($bool)) ? true : $bool;
	}
}