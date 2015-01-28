<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'patterns'=>[
		''=>[
			'patterns'=>[
				'a0'=>[
					'name'=>'a0',
					'action'=>'test.flow.LoginRequestAction::aaa',
				],
				'a1'=>[
					'name'=>'a1',
					'action'=>'test.flow.LoginRequestAction::aaa',
				],
			],
		],
		'sub'=>[
			'patterns'=>[
				'b0'=>[
					'name'=>'b0',
					'action'=>'test.flow.LoginRequestAction::aaa',
				],
				'b1'=>[
					'name'=>'b1',
					'action'=>'test.flow.LoginRequestAction::aaa',
				],
			],
		],
		'z1'=>[
				'name'=>'z1',
				'action'=>'test.flow.LoginRequestAction::aaa',
		],
		'dt'=>['action'=>'ebi.Dt']
	]
]);


