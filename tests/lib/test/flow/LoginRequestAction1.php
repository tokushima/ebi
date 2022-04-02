<?php
namespace test\flow;
/**
 * 
 * @author tokushima
 * @login @['type'=>'test\model\Member1']
 */
class LoginRequestAction1 extends \ebi\flow\AutomapLoginRequest{
	/**
	 * @automap
	 */
	public function aaa(){
		return ['abc'=>123];
	}
	/**
	 * @automap
	 */
	public function bbb(){
		if(!$this->is_user_logged_in()){
			throw new \LogicException('login required');
		}
		return ['abc'=>123];
	}
}