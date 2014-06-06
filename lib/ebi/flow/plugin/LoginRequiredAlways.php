<?php
namespace ebi\flow\plugin;
/**
 * do_login以外の場合にログインしていなければ例外をだす
 * @author tokushima
 *
 */
class LoginRequiredAlways{
	public function before_login_required(\ebi\flow\Request $req){
		if(!$req->is_login()){
			\ebi\HttpHeader::send_status(401);
			throw new \ebi\exception\LogicException('Unauthorized');
		}
	}
}