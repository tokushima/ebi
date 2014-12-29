<?php
include_once('bootstrap.php');

\ebi\Flow::app([
	'patterns'=>[
		''=>[
			'name'=>'start',
			'template'=>'start.html'
		],
		'calc'=>[
			'name'=>'calc',
			'template'=>'calc.html',
			'action'=>'my.Calc::add'
		],
		'days/(\d{8})'=>[
			'name'=>'days',
			'action'=>function($datestring){
				$time = strtotime($datestring.' 00:00:00');
				return [
					'a'=>date('Y/m/d'),
					'b'=>date('Y/m/d',$time),
					'days'=>sprintf('%d',((time() - $time) / 86400))
				];
			}
		],
		'dt'=>['name'=>'dt','action'=>'ebi.Dt'],
	]
]);

