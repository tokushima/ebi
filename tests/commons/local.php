<?php
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
		'handler'=>\ebi\LogMailSender::class,
	],
	'ebi\Session'=>[
		'handler'=>\ebi\SessionDao::class,
	],
	'ebi\Mail'=>[
		'handler'=>\ebi\SmtpBlackholeDao::class,
	],
	'ebi\Flow'=>[
//		'exception_trace'=>true,
		'app_url'=>'http://localhost:8000/**',
// 		'secure'=>false,
		'accept_debug'=>true,
		'handler'=>\test\flow\plugin\ErrorLog::class,
	],
	'ebi\flow\plugin\No'=>[ // x
		'abc'=>1,
	],
	'ebi\flow\Request'=>[
		'cors_origin'=>'http://localhost:8000',
	],
	'ebi\Dt'=>[
		'test_dir'=>dirname(__DIR__).'/test',
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

