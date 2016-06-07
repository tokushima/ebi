<?php
\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[
			'ebi.SessionDao'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebitest'],
			'*'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebitest'],
			'test.db.ReplicationSlave'=>['type'=>'ebi.MysqlUnbufferedConnector','dbname'=>'ebitest'],
			'test.db.Unbuffered'=>['type'=>'ebi.MysqlUnbufferedConnector','dbname'=>'ebitest'],
		]
	],
]);

include_once('./local.php');

