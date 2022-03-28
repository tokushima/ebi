<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'plugins'=>[
		'test\flow\plugin\Login1',
	],
	'patterns'=>[
		'login_url'=>[
			'name'=>'login',
			'action'=>'ebi\flow\Request::do_login',
			'logged_in_after'=>'aaa',
			'plugins'=>['test\flow\plugin\Login2'],
		],
		'logout_url'=>[
			'name'=>'logout',
			'action'=>'ebi\flow\Request::do_logout',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test\flow\LoginRequestAction1::aaa',
		],
		'not_user_perm'=>[
			'name'=>'not_user_perm',
			'action'=>'test\flow\LoginRequestAction1::not_user_perm',
		],
		'aaa'=>[
			'name'=>'aaa',
			'action'=>'test\flow\LoginRequestAction1::aaa',
		],
		'bbb'=>[
			'name'=>'bbb',
			'action'=>'test\flow\LoginRequestAction1::bbb',
		],
		'notype'=>[
			'name'=>'notype',
			'action'=>'test\flow\LoginRequestNoTypeAction::aaa',
		],
		'othertype'=>[
			'name'=>'othertype',
			'action'=>'test\flow\LoginRequestOtherTypeAction::aaa',
		],
		'automap'=>[
			'name'=>'automap',
			'action'=>'test\flow\LoginRequestAction1'
		],
		'dt'=>['action'=>'ebi\Dt']
	]
]);


