<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'patterns'=>[
		''=>['action'=>'ebi.Dt'],
		'abc'=>['template'=>'abc.html'],
	]
]);
