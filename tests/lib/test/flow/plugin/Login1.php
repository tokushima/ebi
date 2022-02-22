<?php
namespace test\flow\plugin;

class Login1{
	/**
	 * @request string $user ユーザ名1
	 * @request string $password パスワード1
	 * @param \ebi\flow\Request $req
	 * @return bool
	 */
	public function login_condition(\ebi\flow\Request $req){
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1(123));
			return true;
		}
		return false;
	}
}
