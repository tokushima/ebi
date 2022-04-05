<?php
include_once('bootstrap.php');
\ebi\Flow::app([
	'auth'=>\test\flow\plugin\Login6::class,
	'patterns'=>[
		'fake_login'=>[
			'name'=>'fake_login',
			'action'=>'test\flow\LoginRequestAction1::do_login',
			'auth'=>\test\flow\plugin\Login5::class,
		],

		'action6'=>[
			'action'=>'test\flow\LoginRequestAction6',
		],
	]
]);


