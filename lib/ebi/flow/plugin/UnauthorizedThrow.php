<?php
namespace ebi\flow\plugin;
/**
 * do_login以外の場合にログインしていなければ例外
 * @author tokushima
 */
class UnauthorizedThrow{
	/**
	 * @plugin ebi\flow\Request
	 * @param \ebi\flow\Request $req
	 */
	public function before_login_redirect(\ebi\flow\Request $req){
		if(!$req->is_user_logged_in()){
			\ebi\HttpHeader::send_status(401);
			throw new \ebi\exception\UnauthorizedException('Unauthorized');
		}
	}
}