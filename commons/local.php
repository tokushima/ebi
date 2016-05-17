<?php
\ebi\Conf::set([
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
			'test.*',
		],
//		'phpinfo'=>false,
//		'config'=>false,
//		'model'=>false,
//		'data'=>false,
	],
]);

$vendor = [];
$dir = str_replace('\\','/',realpath(dirname(__DIR__).DIRECTORY_SEPARATOR.'src').DIRECTORY_SEPARATOR);

foreach(\ebi\Util::ls($dir,true,'/\.php$/') as $f){
 	if(strpos($f->getPathname(),'cmd') === false){
		$path = str_replace('/','.',str_replace($dir,'',substr($f->getPathname(),0,-4)));
		$vendor[] = $path;
	}
}
\ebi\Conf::set('ebi.Dt','use_vendor',$vendor);



\ebi\Conf::set_class_plugin([
	'ebi.Mail'=>['ebi.SmtpBlackholeDao'],
	'ebi.Session'=>['ebi.SessionDao'],
	'ebi.Log'=>['ebi.LogMailSender'],
]);

