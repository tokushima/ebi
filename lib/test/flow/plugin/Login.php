<?php
namespace test\flow\plugin;

class Login{
	public function login_condition(\ebi\flow\Request $req){
		if($req->is_post() && $req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member());
			return true;
		}
		return false;
	}
}
