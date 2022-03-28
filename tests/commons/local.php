<?php

\ebi\Conf::set_handler([
	'ebi\Log'=>\ebi\LogMailSender::class,
	'ebi\Session'=>\ebi\SessionDao::class,
	'ebi\Mail'=>\ebi\SmtpBlackholeDao::class,
	'ebi\Flow'=>\test\flow\plugin\ErrorLog::class,
]);

\ebi\Conf::set([
	'ebi\Conf'=>[
		'appmode_group'=>[
			'dev'=>['local'],
		],
		'session_lifetime'=>60,
		'session_sid_length'=>255,
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
	'ebi\flow\plugin\No'=>[ // x
		'abc'=>1,
	],
	'ebi\flow\Request'=>[
		'cors_origin'=>'http://localhost:8000',
	],
	'ebi\Dt'=>[
		'test_dir'=>dirname(__DIR__).'/test',
		'ignore'=>[
			'test\*',
		],
		'use_vendor'=>[
			'ebi\SmtpBlackholeDao',
			'ebi\SessionDao',
			'ebi\UserRememberMeDao',
			'test\model\DeprecatedClass',
		],
	],
	'ebi\Dao'=>[
		'connection'=>[
			'*'=>['type'=>'ebi\SqliteConnector','timezone'=>'+09:00'],
			// '*'=>['type'=>'ebi\MysqlConnector','timezone'=>'+09:00','host'=>'127.0.0.1','user'=>'root','password'=>'root','name'=>'my_testdb'],
			'test\db\Abc'=>['type'=>'ebi\SqliteConnector','timezone'=>'+09:00'],
		]
	],
]);

\ebi\Conf::set_class_plugin([
	'test\flow\RequestFlow'=>['test\plugin\RequestPlugin'],
]);


