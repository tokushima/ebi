<?php
namespace test\flow;
/**
 * 
 * @author tokushima
 * @login
 */
class LoginRequestNoTypeAction extends \ebi\flow\Request{
	public function aaa(){
		return ['abc'=>123];
	}
}