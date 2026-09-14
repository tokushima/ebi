<?php
namespace test\flow;

use \ebi\Attribute\Route;
use \ebi\Attribute\Login;

#[Login(type:'test\model\Member1')]
class LoginRequestAction1 extends \ebi\flow\AutomapLoginRequest{
	#[Route]
	public function aaa(){
		return ['abc'=>123];
	}
	#[Route]
	public function bbb(){
		if(!$this->is_user_logged_in()){
			throw new \LogicException('login required');
		}
		return ['abc'=>123];
	}
}