<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'secure'=>true,
	'patterns'=>[
		''=>['action'=>'ebi.Dt','mode'=>'local'],
		'abc'=>['template'=>'abc.html'],
		'rest/search/(.+)'=>['action'=>'\ebi\flow\Rest::search']
	]
]);
