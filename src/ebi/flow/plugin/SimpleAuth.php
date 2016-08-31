<?php
namespace ebi\flow\plugin;
/**
 * ユーザ認証
 * @author tokushima
 *
 */
class SimpleAuth{
	public function login_condition(\ebi\flow\Request $req){
		$users = \ebi\Conf::gets('users');
		
		foreach($users as $user_name => $password){
			if(
				$user_name == $req->in_vars('user_name') &&
				$password == $req->in_vars('password')
			){
				$req->user(new \ebi\User($user_name));
				return true;
			}
		}
		return false;
	}
}