<?php
\ebi\Conf::set([
	\ebi\Conf::class=>[
		'appmode_group'=>[
			'dev'=>['local','mamp'],
		],
		'session_lifetime'=>1,
	],
	\ebi\Log::class=>[
		'level'=>'debug',
		'file'=>dirname(__DIR__).'/work/ebi.log',
	],
	'ebi.Flow'=>[
//		'exception_trace'=>true,
		'app_url'=>'http://localhost:8000/**',
// 		'secure'=>false,
		'accept_debug'=>true,
	],
	\ebi\flow\plugin\Cors::class=>[
		'origin'=>'http://localhost:8000',
	],
	'ebi.Dt'=>[
		'test_dir'=>dirname(__DIR__).'/test',
		'ignore'=>[
//			'test.*',
		],
		'use_vendor'=>[
			\ebi\SmtpBlackholeDao::class,
			\ebi\SessionDao::class,
			\ebi\UserRememberMeDao::class,
			\test\model\DeprecatedClass::class,
		],
//		'phpinfo'=>false,
//		'config'=>false,
//		'model'=>false,
//		'data'=>false,
	],
	\ebi\Dao::class=>[
		'connection'=>[
			'*'=>['type'=>\ebi\SqliteConnector::class,'timezone'=>'+09:00'],
		]
	],
]);

\ebi\Conf::set_class_plugin([
	'ebi.Mail'=>[\ebi\SmtpBlackholeDao::class],
	'ebi.Session'=>[\ebi\SessionDao::class],
// 	'ebi.Log'=>[\ebi\LogMailSender::class],
	'ebi.Log'=>['ebi.LogMailSender'],
	\test\flow\RequestFlow::class=>[\test\plugin\RequestPlugin::class],
]);


