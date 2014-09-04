<?php
namespace test\flow;
/**
 * 
 * @author tokushima
 * @login @['type'=>'test.model.Member']
 */
class LoginRequestAction extends \ebi\flow\Request{
	public function aaa(){
		return ['abc'=>123];
	}
	public function bbb(){
		if(!$this->is_login()){
			throw new \LogicException('login required');
		}
		return ['abc'=>123];
	}
}