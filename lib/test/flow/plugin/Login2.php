<?php
namespace test\flow\plugin;

class Login2{
	public function login_condition(\ebi\flow\Request $req){
		if($req->is_post() && $req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member2());
			return true;
		}
		return false;
	}
}
