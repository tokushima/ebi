<?php
namespace test\flow;
/**
 * 
 * @author tokushima
 * @login @['type'=>'\test\model\Member2']
 */
class LoginRequestOtherTypeAction extends \ebi\flow\Request{
	public function aaa(){
		return ['abc'=>123];
	}
}