<?php
include_once('bootstrap.php');
/**
 * automapの場合はdo_loginにリダイレクトされる
 */
\ebi\Flow::app([
	'auth'=>\test\flow\plugin\Login2::class,
	'patterns'=>[
		'automap'=>[
			'name'=>'automap_action',
			'action'=>'test\flow\LoginRequestAction2' // <--
		],
		'dt'=>['action'=>'ebi\Dt']
	]
]);


