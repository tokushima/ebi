<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'plugins'=>'test\flow\plugin\Login1',
	'patterns'=>[
		'login_url'=>[
			'name'=>'login',
			'action'=>'ebi\flow\Request::do_login',
			'template'=>'login.html',
			'logged_in_after'=>'aaa',
		],
		'logout_url'=>[
			'name'=>'logout',
			'action'=>'ebi\flow\Request::do_logout',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test\flow\LoginRequestAction1::aaa',
		]
	]
]);


