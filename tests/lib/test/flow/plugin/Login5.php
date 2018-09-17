<?php
namespace test\flow\plugin;

class Login5{
	public function login_condition(\ebi\flow\Request $req){
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1(987));

			
			return true;
		}
		return false;
	}
	
	
	/**
	 * ログイン後の処理
	 * @param \ebi\flow\Request $req
	 */
	public function after_login(\ebi\flow\Request $req){
		\ebi\Log::trace(__METHOD__);
		$req->set_logged_in_redirect_to($req->in_vars('after_login_redirect'));
	}
}
