<?php
\ebi\Conf::set([
	\ebi\Conf::class=>[
		'appmode_group'=>[
			'dev'=>['local','mamp'],
		],
	],
	\ebi\Log::class=>[
		'level'=>'debug',
		'file'=>dirname(__DIR__).'/work/ebi.log',
	],
	\ebi\Flow::class=>[
//		'exception_trace'=>true,
		'app_url'=>'http://localhost:8000/**',
		'secure'=>false,
		'accept_debug'=>true,
	],
	\ebi\flow\plugin\Cors::class=>[
		'origin'=>'http://localhost:8000',
	],
	\ebi\Dt::class=>[
		'ignore'=>[
//			'test.*',
		],
		'use_vendor'=>[
			\ebi\SmtpBlackholeDao::class,
			\ebi\SessionDao::class,				
		],
//		'phpinfo'=>false,
//		'config'=>false,
//		'model'=>false,
//		'data'=>false,
	],
	\ebi\Dao::class=>[
		'connection'=>[
			'*'=>['type'=>'ebi.SqliteConnector','timezone'=>'+09:00'],
		]
	],
]);

\ebi\Conf::set_class_plugin([
	'ebi.Mail'=>[\ebi\SmtpBlackholeDao::class],
	'ebi.Session'=>[\ebi\SessionDao::class],
	'ebi.Log'=>[\ebi\LogMailSender::class],
]);

