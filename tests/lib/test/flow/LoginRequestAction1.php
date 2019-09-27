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
		if(!$this->is_user_logged_in()){
			throw new \LogicException('login required');
		}
		return ['abc'=>123];
	}
	/**
	 * @user_role 100
	 */
	public function not_user_perm(){
		
	}
}