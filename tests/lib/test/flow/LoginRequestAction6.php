<?php
namespace test\flow;
/**
 * @login @['type'=>'test\model\Member1', 'user_role'=>100]
 */
class LoginRequestAction6 extends \ebi\flow\AutomapLoginRequest{
	/**
	 * @automap
	 */
	public function aaa(){
		return ['abc'=>123];
	}
}