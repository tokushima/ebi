<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'plugins'=>'test.flow.plugin.Login',
	'patterns'=>[
		'login_url'=>[
			'name'=>'login',
			'action'=>'ebi.flow.Request::do_login',
			'args'=>['login_redirect'=>'aaa'],
		],
		'logout_url'=>[
			'name'=>'logout',
			'action'=>'ebi.flow.Request::do_logout',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test.flow.LoginRequestAction::aaa',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test.flow.LoginRequestAction::aaa',
		],
		'bbb'=>[
			'name'=>'bbb',
			'action'=>'test.flow.LoginRequestAction::bbb',
		],
	]
]);


