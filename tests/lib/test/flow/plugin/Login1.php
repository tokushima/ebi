<?php
namespace test\flow\plugin;

class Login1{
	public function login_condition(\ebi\flow\Request $req){
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1());
			return true;
		}
		return false;
	}
}
