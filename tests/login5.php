<?php
include_once('bootstrap.php');
/**
 * after_login_redirect
 */
\ebi\Flow::app([
	'plugins'=>[
		\test\flow\plugin\Login5::class,
		\ebi\flow\plugin\UnauthorizedThrow::class,
	],
	'patterns'=>[
		'login_url'=>[
			'name'=>'login_url',
			'action'=>'ebi\flow\Request::do_login',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test\flow\LoginRequestAction1::aaa',
		],
	]
]);


