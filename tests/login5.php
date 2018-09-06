<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'patterns'=>[
		'login_url'=>[
			'name'=>'login_abc', // name=loginにすると他URLを読んだ時に自動でリダイレクトされるので別の名前にする
			'action'=>'ebi.flow.Request::do_login',
			'plugins'=>[
				'test.flow.plugin.Login1',
			],
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test.flow.LoginRequestAction1::aaa',
		],
		'force_login'=>[
			'name'=>'force_login',
			'action'=>'test.flow.RequestFlow::force_login_redirect',
		],			
	]
]);


