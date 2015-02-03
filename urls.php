<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'plugins'=>[
		'test.flow.plugin.None',	
	],
	'patterns'=>[
		''=>[],
		'abc'=>['template'=>'index.html'],
		'def'=>['template'=>'index.html'],
		'dt'=>['action'=>'ebi.Dt'],
		'app-def'=>['app'=>'def'],
		'app'=>['app'=>'abc','name'=>'newapp'],
		'secure'=>['app'=>'secure'],
	]
]);

