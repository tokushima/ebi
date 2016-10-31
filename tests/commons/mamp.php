<?php
\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[
			\ebi\SessionDao::class=>['type'=>\ebi\MysqlConnector::class,'name'=>'ebitest'],
			'*'=>['type'=>\ebi\MysqlConnector::class,'name'=>'ebitest','timezone'=>'+09:00'],
			\test\db\ReplicationSlave::class=>['type'=>\ebi\MysqlUnbufferedConnector::class,'name'=>'ebitest'],
			'\\test\\db\\Unbuffered'=>['type'=>\ebi\MysqlUnbufferedConnector::class,'name'=>'ebitest'],
		]
	],
]);

include_once(__DIR__.'/local.php');

