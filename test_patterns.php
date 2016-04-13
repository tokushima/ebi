<?php
include_once('autoload.php');

\ebi\Flow::app([
	''=>[
		'patterns'=>[
			'a0'=>[
				'name'=>'a0',
				'action'=>'test.flow.LoginRequestAction1::aaa',
			],
			'a1'=>[
				'name'=>'a1',
				'action'=>'test.flow.LoginRequestAction1::aaa',
			],
		],
	],
	'sub'=>[
		'patterns'=>[
			'b0'=>[
				'name'=>'b0',
				'action'=>'test.flow.LoginRequestAction1::aaa',
			],
			'b1'=>[
				'name'=>'b1',
				'action'=>'test.flow.LoginRequestAction1::aaa',
			],
		],
	],
	'z1'=>[
			'name'=>'z1',
			'action'=>'test.flow.LoginRequestAction1::aaa',
	],
	'dt'=>['action'=>'ebi.Dt']
]);


