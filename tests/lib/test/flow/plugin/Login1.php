<?php
namespace test\flow\plugin;
use \ebi\Attribute\Parameter;

class Login1 extends \ebi\flow\AuthenticationHandler{
	#[Parameter(name:'user', type:'string', summary:'ユーザ名1')]
	#[Parameter(name:'password', type:'string', summary:'パスワード1')]
	public function login_condition(\ebi\flow\Request $req): bool{
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1(123));
			
			return true;
		}
		return false;
	}
}
