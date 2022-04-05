<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'auth'=>\test\flow\plugin\Login1::class,
	'patterns'=>[
		'login_url'=>[
			'name'=>'login',
			'action'=>'ebi\flow\Request::do_login',
			'template'=>'login.html',
		],
		'logout_url'=>[
			'name'=>'logout',
			'action'=>'ebi\flow\Request::do_logout',
			'after'=>'temp2',
		],
		'temp1'=>[
			'name'=>'temp1',
			'action'=>'test\flow\LoginRequestAction1::aaa',
			'template'=>'abc.html',
		],
		'temp2'=>[
			'name'=>'temp2',
			'action'=>'test\flow\LoginRequestAction1::aaa',
			'template'=>'abc.html',
		],
	]
]);


