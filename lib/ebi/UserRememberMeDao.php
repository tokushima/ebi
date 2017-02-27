<?php
namespace ebi;
use \ebi\Q;
/**
 * @var serial $id
 * @var integer $user_id
 * @var string $token @['auto_code_add'=>true,'max'=>64]
 * @var string $key @['auto_code_add'=>true,'max'=>20]
 * @var timestamp $expire_date
 * @author tokushima
 * TODO
 */
class UserRememberMeDao extends \ebi\Dao{
	protected $id;
	protected $user_id;
	protected $token;
	protected $key;
	protected $expire_date;
	
	private static function encrypt($user_id){
		return sha1(\ebi\Conf::get('salt').$user_id);
	}
	public static function set(\ebi\flow\Request $req){
		if($req->user() instanceof \ebi\User){
			$expire = time() + 5184000; // 2day
			
			try{
				$self = static::find_get(Q::eq('user_id',$req->user()->id()));
			}catch(\ebi\exception\NotFoundException $e){
				$self = new static();
				$self->user_id($req->user()->id());
			}
			$self->expire_date($expire);
			$self->save();
			
			$req->write_cookie('_lt',$self->token(),$expire);
			$req->write_cookie('_sk',$self->key(),$expire);
			$req->write_cookie('_ui',self::encrypt($self->user_id()),$expire);
		}
	}
	public static function get(\ebi\flow\Request $req){

	}
}
