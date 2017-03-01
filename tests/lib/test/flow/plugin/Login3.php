<?php
namespace test\flow\plugin;

class Login3{
	public function login_condition(\ebi\flow\Request $req){
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1());
			return true;
		}
		return false;
	}
	public function remember_me(\ebi\flow\Request $req){
		$req->user(new \test\model\Member1());
		return true;
	}
}
