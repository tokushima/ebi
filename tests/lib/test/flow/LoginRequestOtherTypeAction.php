<?php
namespace test\flow;
/**
 * @login @['type'=>'\test\model\Member2']
 */
class LoginRequestOtherTypeAction extends \ebi\flow\Request{
	public function aaa(){
		return ['abc'=>123];
	}
}