<?php
namespace test\flow\plugin;
use \ebi\Attribute\Parameter;

class Login2 extends \ebi\flow\AuthenticationHandler{
	#[Parameter(name:'user', type:'string', summary:'ユーザ名2')]
	#[Parameter(name:'password', type:'string', summary:'パスワード2')]
	public function login_condition(\ebi\flow\Request $req): bool{
		if($req->is_post() && $req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member2());
			
			return true;
		}
		return false;
	}
}
