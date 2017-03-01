<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'plugins'=>'test.flow.plugin.Login3',
	'patterns'=>[
		'login_url'=>[
			'name'=>'login',
			'action'=>'ebi.flow.Request::do_login',
		],
		'logout_url'=>[
			'name'=>'logout',
			'action'=>'ebi.flow.Request::do_logout',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test.flow.LoginRequestAction1::aaa',
		],
	]
]);


