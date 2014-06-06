<?php
include_once('bootstrap.php');

$flow = new \ebi\Flow();
$flow->execute([
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


