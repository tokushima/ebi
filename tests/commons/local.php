<?php
\ebi\Conf::set([
	'ebi\Conf'=>[
		'appmode_group'=>[
			'dev'=>['local'],
		],
		'session_lifetime'=>60,
	],
	'ebi\Log'=>[
		'level'=>'debug',
		'file'=>dirname(__DIR__).'/work/ebi.log',
	],
	'ebi\Flow'=>[
//		'exception_trace'=>true,
		'app_url'=>'http://localhost:8000/**',
// 		'secure'=>false,
		'accept_debug'=>true,
	],
	'ebi\flow\plugin\Cors'=>[
		'origin'=>'http://localhost:8000',
	],
	'ebi\Dt'=>[
		'test_dir'=>dirname(__DIR__).'/test',
		'ignore'=>[
//			'test.*',
		],
		'use_vendor'=>[
			'ebi\SmtpBlackholeDao',
			'ebi\SessionDao',
			'ebi\UserRememberMeDao',
			'test\model\DeprecatedClass',
		],
//		'phpinfo'=>false,
//		'config'=>false,
//		'model'=>false,
//		'data'=>false,
	],
	'ebi\Dao'=>[
		'connection'=>[
			'*'=>['type'=>\ebi\SqliteConnector::class,'timezone'=>'+09:00'],
		]
	],
]);

\ebi\Conf::set_class_plugin([
	'ebi\Mail'=>['ebi\SmtpBlackholeDao'],
	'ebi\Session'=>['ebi\SessionDao'],
// 	'ebi\Log'=>['ebi\LogMailSender'],
	'ebi\Log'=>['ebi\LogMailSender'],
	'test\flow\RequestFlow'=>['test\plugin\RequestPlugin'],
	'ebi\Flow'=>['test\flow\plugin\ErrorLog'],
]);


\ebi\Benchmark::register_shutdown(\ebi\Conf::work_path('benchmark.csv'));

