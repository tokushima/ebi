<?php
namespace test\flow;
/**
 * @login @['type'=>'test\model\Member1']
 */
class LoginRequestAction7 extends \ebi\flow\AutomapLoginRequest{
	/**
	 * @automap
	 */
	public function aaa(){
		return ['abc'=>123];
	}
	/**
	 * bare logout アクション（do_logoutではない）
	 * @automap
	 */
	public function logout(){
		return ['logged_out'=>true];
	}
}
