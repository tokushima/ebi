<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'plugins'=>'test.flow.plugin.Login',
	'patterns'=>[
		'login_url'=>[
			'name'=>'login',
			'action'=>'ebi.flow.Request::do_login',
			'template'=>'login.html',
			'args'=>['login_redirect'=>'aaa'],
		],
		'logout_url'=>[
			'name'=>'logout',
			'action'=>'ebi.flow.Request::do_logout',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test.flow.LoginRequestAction::aaa',
		]
	]
]);


