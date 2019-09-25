<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'secure'=>true,
	'patterns'=>[
		'auto'=>[
			'name'=>'auto',
			'action'=>'test\flow\AutoAction',
		],
		'abc'=>[
			'secure'=>false,
			'action'=>function(){
				return [
					'A'=>'b',
				];
			}
		],
		'def'=>[
			'action'=>function(){
				return [
					'A'=>'b',
				];
			}
		]
	]
]);


