<?php
\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[
			'ebi.SessionDao'=>['host'=>dirname(__DIR__).'/work/db','dbname'=>'session.db'],
			'*'=>['host'=>dirname(__DIR__).'/work/db','dbname'=>'all.db'],
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
		'app_url'=>'http://127.0.0.1:8888/*',
		'secure'=>false,
	],
	'ebi.Dt'=>[
		'use_vendor'=>[
			'ebi.SmtpBlackholeDao',
			'ebi.SessionDao',
			'ebi.queue.plugin.Dao.QueueDao'
		],
	]
]);
	

\ebi\Conf::set_class_plugin([
	'ebi.Mail'=>['ebi.SmtpBlackholeDao'],
	'ebi.Session'=>['ebi.SessionDao'],
]);



