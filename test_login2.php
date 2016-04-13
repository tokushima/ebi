<?php
include_once('autoload.php');

\ebi\Flow::app([
	'plugins'=>'test.flow.plugin.Login2',
	'patterns'=>[
		'automap'=>[
			'name'=>'automap',
			'action'=>'test.flow.LoginRequestAction2'
		],
		'dt'=>['action'=>'ebi.Dt']
	]
]);


