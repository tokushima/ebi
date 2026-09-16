<?php
namespace test\flow\plugin;

class Login5 extends \ebi\flow\AuthenticationHandler{
	public function login_condition(\ebi\flow\Request $req): bool{
		if($req->in_vars('user') == 'tokushima' && $req->in_vars('password') == 'hogehoge'){
			$req->user(new \test\model\Member1(987));

			return true;
		}
		return false;
	}
	
	public function after_login(\ebi\flow\Request $req): void{
		// このプラグインは after_login_redirect を送らないパターン(login6::fake_login)からも使われる。
		// 未指定時は null が渡り set_logged_in_redirect_to(string) で TypeError になるため送出時のみ設定する。
		$url = $req->in_vars('after_login_redirect');

		if(!empty($url)){
			$req->set_logged_in_redirect_to($url);
		}
	}
}
