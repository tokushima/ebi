<?php
namespace ebi;
/**
 * Remember me
 * @var serial $id
 * @var string $user_id @['max'=>128]
 * @var string $token @['auto_code_add'=>true,'max'=>80]
 * @var string $key @['auto_code_add'=>true,'max'=>45]
 * @var datetime $expire_date
 */
class UserRememberMeDao extends \ebi\Dao{
	protected $id;
	protected $user_id;
	protected $token;
	protected $key;
	protected $expire_date;
	
	private static function crypt(string $user_id): string{
		/**
		 * @param string $salt user_idのハッシュ用salt
		 */
		return sha1(\ebi\Conf::get('salt',__FILE__).$user_id);
	}
	private static function name(\ebi\flow\Request $req, string $k): string{
		return '_'.md5($req->user_login_session_id().__FILE__.$k);
	}
	
	/**
	 * login_condition/remember_meで利用しtokenをセットする
	 */
	public static function write_cookie(\ebi\flow\Request $req): void{
		if($req->user() instanceof \ebi\User){
			try{
				$self = static::find_get(Q::eq('user_id',$req->user()->id()));
			}catch(\ebi\exception\NotFoundException $e){
				$self = new static();
				$self->user_id($req->user()->id());
			}
			/**
			 * @param int $lifetime クッキーの保存期間
			 */
			$expire = time() + \ebi\Conf::get('lifetime',5184000); // 60day
			$codebase = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!#%&@.';
			
			for($i=0;$i<100;$i++){
				$token = \ebi\Code::rand($codebase,80);
				$key = \ebi\Code::rand($codebase,45);
				
				if(static::find_count(Q::eq('token',$token),Q::eq('key',$key)) === 0){
					break;
				}
				$token = '';
			}
			if(!empty($token)){
				$self->token($token);
				$self->key($key);
				$self->expire_date($expire);
				$self->save();
				
				setcookie(self::name($req,'token'),$self->token(),$expire);
				setcookie(self::name($req,'key'),$self->key().'/'.self::crypt($self->user_id()),$expire);
			}
		}
	}
	/**
	 * remember_meで利用しuser_idを取得する
	 */
	public static function read_cookie(\ebi\flow\Request $req): string{
		$token = $_COOKIE[self::name($req,'token')] ?? null;
		
		if(!empty($token)){
			if(rand(1,10) == 5){
				foreach(static::find(Q::lt('expire_date',time()),new \ebi\Paginator(10)) as $obj){
					$obj->delete();
				}
			}
			$sk = explode('/', $_COOKIE[self::name($req,'key')] ?? null);
			
			if(isset($sk[1])){
				[$key, $id] = $sk;
				
				try{
					$self = static::find_get(
						Q::eq('token',$token),
						Q::eq('key',$key),
						Q::gt('expire_date',time())
					);
					
					if(self::crypt($self->user_id()) === $id){
						return $self->user_id();
					}
					
					// 無効なら削除
					$self->delete();
				}catch(\ebi\exception\NotFoundException $e){
				}
			}
		}
		throw new \ebi\exception\NotFoundException();
	}
	
	/**
	 * before_do_logoutで利用する
	 */
	public static function delete_cookie(\ebi\flow\Request $req): void{
		if($req->user() instanceof \ebi\User){
			try{
				$self = static::find_get(Q::eq('user_id',$req->user()->id()));
				$self->delete();
			}catch(\ebi\exception\NotFoundException $e){
			}
		}
		setcookie(self::name($req,'token'), '', time() - 1209600);
		setcookie(self::name($req,'key'), '', time() - 1209600);
	}
}
