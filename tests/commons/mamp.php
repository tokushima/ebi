<?php
\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[
			'ebi.SessionDao'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebitest','timezone'=>'+09:00'],
			'*'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebitest','timezone'=>'+09:00'],
			'test.db.ReplicationSlave'=>['type'=>'ebi.MysqlUnbufferedConnector','dbname'=>'ebitest','timezone'=>'+09:00'],
			'test.db.Unbuffered'=>['type'=>'ebi.MysqlUnbufferedConnector','dbname'=>'ebitest','timezone'=>'+09:00'],
		]
	],
	'ebi.Flow'=>[
	//		'exception_trace'=>true,
		'app_url'=>'http://localhost/ebi/test_app/**',
		'secure'=>false,
		'accept_debug'=>true,
	],
	'ebi.flow.plugin.Cors'=>[
		'origin'=>'http://localhost/ebi/test_app',
	],		
]);

include_once(__DIR__.'/local.php');

