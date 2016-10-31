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

foreach(\ebi\Dt::classes('\ebi\Dao') as $class_info){
	if(\cmdman\Std::read('create table','n',['y','n']) == 'y'){
		foreach(\ebi\Dt::create_table() as $model){
			\cmdman\Std::println_primary('Created '.$model[1]);
		}
	}
	break;
}
if(!is_file($path.'/test/testman.phar') && !is_file($path.'/testman.phar')){
	if(\cmdman\Std::read('getting testman?','n',['y','n']) == 'y'){
		\ebi\Util::mkdir($path.'/test');
		
		file_put_contents($f=$path.'/test/testman.phar',file_get_contents('http://git.io/testman.phar'));
		\cmdman\Std::println_success('Written file '.$f);
		
		file_put_contents($f=$path.'/test/testman.settings.php',<<< '__SRC__'
<?php
\ebi\Conf::set('ebi.Db','autocommit',true);
\testman\Conf::set('urls',\ebi\Dt::get_urls());
__SRC__
		);
		\cmdman\Std::println_success('Written file '.$f);
		
		file_put_contents($f=$path.'/test/testman.fixture.php',<<< '__SRC__'
<?php
\ebi\Dt::setup();
\ebi\Dt::create_table();
__SRC__
		);
		\cmdman\Std::println_success('Written file '.$f);
		
		file_put_contents($f=$path.'/test/__setup__.php',<<< '__SRC__'
<?php
\ebi\Exceptions::clear();
__SRC__
		);
		\cmdman\Std::println_success('Written file '.$f);
	}
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

$setup_cmd = substr(\ebi\Dt::setup_file(),0,-4).'.cmd.php';
if(is_file($setup_cmd)){
	include($setup_cmd);
}
if(is_file($f=\ebi\Dt::setup_file())){
	\cmdman\Std::println_info('Run setup.');
	\ebi\Dt::setup();
}
\cmdman\Std::println_info('Done.');

