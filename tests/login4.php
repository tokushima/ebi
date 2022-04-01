<?php
include_once('bootstrap.php');
/**
 * remember_me plugin
 */
\ebi\Flow::app([
	'unauthorized_redirect'=>false,
	'auth'=>\test\flow\plugin\Login4::class,
	'patterns'=>[
		'login_url'=>[
			'name'=>'login',
			'action'=>'ebi\flow\Request::do_login',
		],
		'logout_url'=>[
			'name'=>'logout',
			'action'=>'ebi\flow\Request::do_logout',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test\flow\LoginRequestAction1::aaa',
		],
	]
]);


