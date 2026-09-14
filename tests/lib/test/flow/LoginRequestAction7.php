<?php
namespace test\flow;

use \ebi\Attribute\Route;
use \ebi\Attribute\Login;

#[Login(type:'test\model\Member1')]
class LoginRequestAction7 extends \ebi\flow\AutomapLoginRequest{
	#[Route]
	public function aaa(){
		return ['abc'=>123];
	}
	/**
	 * bare logout アクション（do_logoutではない）
	 */
	#[Route]
	public function logout(){
		return ['logged_out'=>true];
	}
}
