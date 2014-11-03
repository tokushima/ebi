<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'patterns'=>[
		''=>['template'=>'index.html'],
		'dt'=>['action'=>'ebi.Dt'],
	]
]);

