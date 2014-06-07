<?php
include_once('bootstrap.php');

$flow = new \ebi\Flow();
$flow->execute([
	'patterns'=>[
		''=>['template'=>'index.html'],
		'dt'=>['action'=>'ebi.Dt'],
	]
]);

