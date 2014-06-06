<?php
include_once('bootstrap.php');

$flow = new \ebi\Flow();
$flow->execute([
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

