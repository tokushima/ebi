<?php
namespace ebi\app;

use \ebi\Attribute\Route;
use \ebi\Attribute\Login;

/**
 * ログイン、リクエストやセッションを処理する
 */
#[Login(type:'ebi\User')]
class RouteLoginRequest extends \ebi\app\Request{
	#[Route]
	public function do_login(): array{
		return parent::do_login();
	}

	#[Route]
	public function do_logout(): void{
		parent::do_logout();
	}
}
