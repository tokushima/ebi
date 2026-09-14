<?php
namespace test\flow;

use \ebi\Attribute\Route;
use \ebi\Attribute\Login;

#[Login(type:'test\model\Member1', user_role:100)]
class LoginRequestAction6 extends \ebi\flow\AutomapLoginRequest{
	#[Route]
	public function aaa(){
		return ['abc'=>123];
	}
}