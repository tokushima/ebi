<?php
\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[
			'ebi.SessionDao'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebi'],
			'*'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebi'],
			'test.db.ReplicationSlave'=>['type'=>'ebi.MysqlUnbufferedConnector','dbname'=>'ebi'],
			'test.db.Unbuffered'=>['type'=>'ebi.MysqlUnbufferedConnector','dbname'=>'ebi'],
		]
	],

	'ebi.Flow'=>[
		'app_url'=>'http://localhost/ebid/**',
// 		'secure'=>true,
	],
]);

include_once('local.php');

//\ebi\Conf::set('ebi.Dt','media_url','http://localhost/work/media/');
