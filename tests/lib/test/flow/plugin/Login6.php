<?php
namespace test\flow\plugin;

class Login6 extends \ebi\flow\AuthenticationHandler{
	public function login_condition(\ebi\flow\Request $req): bool{
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1(987));
			$req->user()->set_role([100]);

			return true;
		}
		return false;
	}
}
