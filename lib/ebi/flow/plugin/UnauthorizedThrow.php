<?php
namespace ebi\flow\plugin;

class UnauthorizedThrow{
	/**
	 * @plugin \ebi\flow\Request
	 */
	public function before_login_redirect(\ebi\flow\Request $req): void{
		if(!$req->is_user_logged_in()){
			\ebi\HttpHeader::send_status(401);
			throw new \ebi\exception\UnauthorizedException('Unauthorized');
		}
	}
}