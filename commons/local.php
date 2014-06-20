<?php
set_include_path(dirname(__DIR__).'/src'.PATH_SEPARATOR.get_include_path());

date_default_timezone_set('Asia/Tokyo');

\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[
			'ebi.SessionDao'=>['type'=>'ebi.DbConnector','dbname'=>'local.db','host'=>dirname(__DIR__)],
			'*'=>['host'=>dirname(__DIR__),'dbname'=>'local.db'],
		]
	],
	'ebi.Log'=>[
		'level'=>'warn',
		'file'=>dirname(__DIR__).'/work/ebi.log',
		'stdout'=>true,
	],
	'ebi.Flow'=>[
		'exception_log_level'=>'warn',
//		'rewrite_entry'=>true,
//		'app_url'=>'http://localhost:8888/urls',
//		'secure'=>false,
	]
]);

\ebi\Conf::set_class_plugin([
	'ebi.Mail'=>['ebi.SmtpBlackholeDao']
]);



