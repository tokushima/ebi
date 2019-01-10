<?php
namespace ebi\flow;
/**
 * ログイン、リクエストやセッションを処理する
 */
class AutomapLoginRequest extends \ebi\flow\Request{
	/**
	 * @automap
	 */
	public function do_login(){
		return parent::do_login();
	}

	/**
	 * @automap
	 */
	public function do_logout(){
		parent::do_logout();
	}
}

