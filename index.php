<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'patterns'=>[
		''=>['action'=>'ebi.Dt','mode'=>'local'],
		'abc'=>['template'=>'abc.html'],
	]
]);
