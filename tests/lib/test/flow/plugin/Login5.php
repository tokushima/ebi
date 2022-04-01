<?php
namespace test\flow\plugin;

class Login5 extends \ebi\flow\AuthenticationHandler{
	public function login_condition(\ebi\flow\Request $req): bool{
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1(987));

			return true;
		}
		return false;
	}
	
	public function after_login(\ebi\flow\Request $req): void{
		$req->set_logged_in_redirect_to($req->in_vars('after_login_redirect'));
	}
}
