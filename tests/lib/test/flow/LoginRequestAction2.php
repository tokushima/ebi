<?php
namespace test\flow;

use \ebi\Attribute\Route;
use \ebi\Attribute\Login;

#[Login(type:'test\model\Member2')]
class LoginRequestAction2 extends \ebi\flow\AutomapLoginRequest{

	/**
	 * @return multitype:number
	 */
	#[Route]
	public function aaa(){
		return ['abc'=>123];
	}
	/**
	 * @throws \LogicException
	 * @return multitype:number
	 */
	#[Route]
	public function bbb(){
		if(!$this->is_user_logged_in()){
			throw new \LogicException('login required');
		}
		return ['abc'=>123];
	}
}