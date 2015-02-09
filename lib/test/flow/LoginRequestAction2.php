<?php
namespace test\flow;
/**
 * 
 * @author tokushima
 * @login @['type'=>'test.model.Member2']
 */
class LoginRequestAction2 extends \ebi\flow\Request{
	/**
	 * @automap
	 * @return multitype:number
	 */
	public function aaa(){
		return ['abc'=>123];
	}
	/**
	 * @automap
	 * @throws \LogicException
	 * @return multitype:number
	 */
	public function bbb(){
		if(!$this->is_login()){
			throw new \LogicException('login required');
		}
		return ['abc'=>123];
	}
}