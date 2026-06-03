<?php
include_once('bootstrap.php');
/**
 * bare logout アクション（do_logoutではない）でも、
 * ログイン後にlogoutに戻らないことを確認するためのentry
 */
\ebi\Flow::app([
	'auth'=>\test\flow\plugin\Login1::class,
	'patterns'=>[
		'login_url'=>[
			'name'=>'login',
			'action'=>'ebi\flow\Request::do_login',
		],
		'logout_url'=>[
			'name'=>'logout',
			'action'=>'test\flow\LoginRequestAction7::logout',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test\flow\LoginRequestAction7::aaa',
		],
	]
]);
