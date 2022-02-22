<?php
namespace test\flow\plugin;

class Login4{
	public function login_condition(\ebi\flow\Request $req){
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1(987));
			\ebi\UserRememberMeDao::write_cookie($req);
			return true;
		}
		return false;
	}
	
	/**
	 * remeber meに記録
	 * @param \ebi\flow\Request $req
	 * @return bool
	 */
	public function remember_me(\ebi\flow\Request $req){
		try{
			$user_id = \ebi\UserRememberMeDao::read_cookie($req);
			$req->user(new \test\model\Member1($user_id));
			
			\ebi\UserRememberMeDao::write_cookie($req);
			
			return true;
		}catch(\ebi\exception\NotFoundException $e){
		}
		return false;
	}
	
	
	/**
	 * ログアウトでremeber meから削除
	 * @param \ebi\flow\Request $req
	 */
	public function before_logout(\ebi\flow\Request $req){
		\ebi\UserRememberMeDao::delete_cookie($req);
	}
}
