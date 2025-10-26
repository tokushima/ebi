<?php
namespace test\flow;
/**
 * @login
 */
class LoginRequestNoTypeAction extends \ebi\flow\Request{
	public function aaa(){
		return ['abc'=>123];
	}
}