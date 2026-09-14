<?php
namespace test\flow;

use \ebi\Attribute\Login;

#[Login(type:'\test\model\Member2')]
class LoginRequestOtherTypeAction extends \ebi\flow\Request{
	public function aaa(){
		return ['abc'=>123];
	}
}