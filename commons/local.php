<?php
\ebi\Conf::set([
	'ebi.Dao'=>[
		'connection'=>[
//			'ebi.SessionDao'=>['host'=>dirname(__DIR__).'/work/db','dbname'=>'session.db'],
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
		'app_url'=>'http://localhost:8000/*',
//		'secure'=>false,
	],
]);

$vendor = [];
$dir = realpath(dirname(__DIR__).'/src').DIRECTORY_SEPARATOR;
foreach(\ebi\Util::ls($dir,true,'/\.php$/') as $f){
	if(strpos($f->getPathname(),'cmd') === false){
		$vendor[] = str_replace('/','.',str_replace($dir,'',substr($f->getPathname(),0,-4)));
	}
}
\ebi\Conf::set('ebi.Dt','use_vendor',$vendor);



\ebi\Conf::set_class_plugin([
	'ebi.Mail'=>['ebi.SmtpBlackholeDao'],
	'ebi.Session'=>['ebi.SessionDao'],
//	'ebi.Queue'=>['ebi.queue.plugin.Dao'],
]);


