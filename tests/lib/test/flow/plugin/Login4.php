<?php
namespace test\flow\plugin;

class Login4 extends \ebi\flow\AuthenticationHandler{
	public function login_condition(\ebi\flow\Request $req): bool{
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1(987));

			\ebi\UserRememberMeDao::write_cookie($req);
			return true;
		}
		return false;
	}
	
	/**
	 * remember meに記録
	 */
	public function remember_me(\ebi\flow\Request $req): bool{
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
	 * ログアウトでremember meから削除
	 */
	public function before_logout(\ebi\flow\Request $req): void{
		\ebi\UserRememberMeDao::delete_cookie($req);
	}
}
