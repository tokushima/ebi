<?php
namespace test\flow;

use \ebi\Attribute\Login;

#[Login]
class LoginRequestNoTypeAction extends \ebi\flow\Request{
	public function aaa(){
		return ['abc'=>123];
	}
}