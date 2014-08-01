<?php
\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[
			'ebi.SessionDao'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebi'],
			'*'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebi'],
			'test.db.ReplicationSlave'=>['type'=>'ebi.MysqlUnbufferedConnector','dbname'=>'ebi'],
		]
	],
]);

\ebi\Conf::set('ebi.Flow','host','http://localhost/');

include_once('local.php');

//\ebi\Conf::set('ebi.Dt','media_url','http://localhost/work/media/');
