<?php
\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[
			'ebi.SessionDao'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebi'],
			'*'=>['type'=>'ebi.MysqlConnector','dbname'=>'ebi'],
		]
	],
]);

\ebi\Conf::set('ebi.Flow','app_url','http://localhost/ebi/test_index.php');
//\ebi\Conf::set('ebi.Flow','app_url','http://localhost/ebi/test_index');

include_once('local.php');
