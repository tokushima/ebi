<?php
/**
 * Application setup
 */

$appmode = defined('APPMODE') ? constant('APPMODE') : 'local';
$cmndir = defined('COMMONDIR') ? constant('COMMONDIR') : str_replace('\\','/',getcwd()).'/commons';

$mode_list = [];
if(is_dir($cmndir)){
	foreach(new \RecursiveDirectoryIterator($cmndir,\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS) as $f){
		if(substr($f->getFilename(),0,1) != '_' && substr($f->getFilename(),-4) == '.php'){
			$mode_list[] = substr($f->getFilename(),0,-4);
		}
	}
}
$default = (empty($appmode) || array_search($appmode,$mode_list) !== false) ? $appmode : 'local';
$mode = \cmdman\Std::read('Application mode',$default,$mode_list);
$settings_file = getcwd().'/__settings__.php';
$path = getcwd();


file_put_contents($settings_file,
	'<?php'
	.PHP_EOL.'define(\'APPMODE\',\''.$mode.'\');'
	.PHP_EOL.'define(\'COMMONDIR\',\''.$cmndir.'\');'
	.PHP_EOL
);
\cmdman\Std::println_success('Written: '.realpath($settings_file));

if(!is_file($f=($cmndir.'/'.$mode.'.php'))){
	\ebi\Util::file_write($f,<<< '__SRC__'
<?php
\ebi\Conf::set([
]);
__SRC__
			);
	\cmdman\Std::println_success('Written: '.realpath($f));
}
if($mode != $appmode){
	\cmdman\Std::println_info('Application mode changed.');
	return;
}else{
	\cmdman\Std::println_info('Application mode is `'.$mode.'`');
}

if(!is_file($f=$path.'/bootstrap.php')){
	$autoload_file = '';
		
	if(class_exists('Composer\Autoload\ClassLoader')){
		$r = new \ReflectionClass('Composer\Autoload\ClassLoader');
		$composer_dir = dirname($r->getFileName());

		if(is_file($bf=realpath(dirname($composer_dir).'/autoload.php'))){
			$autoload_file = str_replace(str_replace("\\",'/',getcwd()).'/','',str_replace("\\",'/',$bf));
		}
	}else{
		foreach(\ebi\Util::ls($path,true,'/ebi\.phar$/') as $p){
			$autoload_file = str_replace(str_replace("\\",'/',getcwd()).'/','',str_replace("\\",'/',$p));
			break;
		}
	}
	if(!empty($autoload_file)){
		file_put_contents($f,'<?php'.PHP_EOL.'include_once(\''.$autoload_file.'\');');
		\cmdman\Std::println_success('Written file '.$f);
	}
}


