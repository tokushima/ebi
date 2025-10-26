<?php
namespace ebi\flow;
/**
 * ログイン、リクエストやセッションを処理する
 * @login @['type'=>'ebi\User']
 */
class AutomapLoginRequest extends \ebi\flow\Request{
	/**
	 * @automap
	 */
	public function do_login(): array{
		return parent::do_login();
	}

	/**
	 * @automap
	 */
	public function do_logout(): void{
		parent::do_logout();
	}
}

