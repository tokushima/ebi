<?php
namespace test\flow\plugin;

class Login2 extends \ebi\flow\AuthenticationHandler{
	/**
	 * @request string $user ユーザ名2
	 * @request string $password パスワード2
	 */
	public function login_condition(\ebi\flow\Request $req): bool{
		if($req->is_post() && $req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member2());
			return true;
		}
		return false;
	}
}
