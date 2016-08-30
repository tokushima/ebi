<?php
\ebi\Conf::set([
	'ebi.Conf'=>[
		'appmode_group'=>[
			'dev'=>['local','mamp'],
		],
	],
	'ebi.Log'=>[
		'level'=>'debug',
		'file'=>dirname(__DIR__).'/work/ebi.log',
	],
	'ebi.Flow'=>[
//		'exception_trace'=>true,
		'app_url'=>'http://localhost:8000/**',
		'secure'=>false,
		'accept_debug'=>true,
	],
	'ebi.flow.plugin.Cors'=>[
		'origin'=>'http://localhost:8000',
	],
	'ebi.Dt'=>[
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
	'ebi.Dao'=>[
		'connection'=>[
			'*'=>['type'=>'ebi.SqliteConnector','timezone'=>'Asia/Tokyo'],
		]
	],
]);

\ebi\Conf::set_class_plugin([
	'ebi.Mail'=>[\ebi\SmtpBlackholeDao::class],
	'ebi.Session'=>[\ebi\SessionDao::class],
	'ebi.Log'=>[\ebi\LogMailSender::class],
]);

