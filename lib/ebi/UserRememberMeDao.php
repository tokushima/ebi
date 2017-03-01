<?php
namespace ebi;
use \ebi\Q;
/**
 * Remember me
 * @var serial $id
 * @var string $user_id @['max'=>255]
 * @var string $token @['auto_code_add'=>true,'max'=>80]
 * @var string $key @['auto_code_add'=>true,'max'=>45]
 * @var timestamp $expire_date
 * @author tokushima
 */
class UserRememberMeDao extends \ebi\Dao{
	protected $id;
	protected $user_id;
	protected $token;
	protected $key;
	protected $expire_date;
	
	private static function encrypt($user_id){
		return hash('sha256',(\ebi\Conf::get('salt',static::class).$user_id),false);
	}
	private static function name(\ebi\flow\Request $req,$k){
		return '_'.sha1($req->user_logged_in_identifier().static::class.$k);
	}
	
	/**
	 * login_condition/remember_meで利用しtokenをセットする
	 * @param \ebi\flow\Request $req
	 */
	public static function set(\ebi\flow\Request $req){
		if($req->user() instanceof \ebi\User){
			try{
				$self = static::find_get(Q::eq('user_id',$req->user()->id()));
			}catch(\ebi\exception\NotFoundException $e){
				$self = new static();
				$self->user_id($req->user()->id());
			}
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

				\ebi\Request::write_cookie(self::name($req,'token'),$self->token(),$expire);
				\ebi\Request::write_cookie(self::name($req,'key'),$self->key().'/'.self::encrypt($self->user_id()),$expire);
			}
		}
	}
	/**
	 * remember_meで利用しuser_idを取得する
	 * @param \ebi\flow\Request $req
	 * @throws \ebi\exception\NotFoundException
	 * @return string
	 */
	public static function get(\ebi\flow\Request $req){
		if(isset($_COOKIE['_lt'])){
			if(rand(1,10) == 5){
				foreach(static::find(Q::lt('expire_date',time()),new Paginator(10)) as $obj){
					$obj->delete();
				}
			}
			$token = \ebi\Request::read_cookie(self::name($req,'token'));
			$sk = explode('/',\ebi\Request::read_cookie(self::name($req,'key')));
			
			if(isset($sk[1])){
				list($key,$id) = $sk;
				
				try{
					$self = static::find_get(
						Q::eq('token',$token),
						Q::eq('key',$key),
						Q::gt('expire_date',time())
					);
					
					if(self::encrypt($self->user_id()) === $id){
						return $self->user_id();
					}
				}catch(\ebi\exception\NotFoundException $e){
				}
			}
		}
		throw new \ebi\exception\NotFoundException();
	}
	
	/**
	 * before_do_logoutで利用する
	 * @param \ebi\flow\Request $req
	 */
	public static function delete(\ebi\flow\Request $req){
		\ebi\Request::delete_cookie(self::name($req,'token'));
		\ebi\Request::delete_cookie(self::name($req,'key'));
	}
}
