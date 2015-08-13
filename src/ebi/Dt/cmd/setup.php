<?php
/**
 * Application setup
 */

$appmode = defined('APPMODE') ? constant('APPMODE') : null;
$cmddir = defined('COMMONDIR') ? constant('COMMONDIR') : (getcwd().'/commons');

$mode_list = [];
if(is_dir($cmddir)){
	foreach(new \RecursiveDirectoryIterator($cmddir,\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS) as $f){
		if(substr($f->getFilename(),-4) == '.php'){
			$mode_list[] = substr($f->getFilename(),0,-4);
		}
	}
}
$default = (empty($appmode) || array_search($appmode,$mode_list) !== false) ? $appmode : 'local';
$mode = \cmdman\Std::read('Application mode',$default,$mode_list);
$settings_file = getcwd().'/__settings__.php';
$path = getcwd();

if($mode != $appmode || !is_file($settings_file)){
	file_put_contents($settings_file,
	'<?php'
		.PHP_EOL.'define(\'APPMODE\',\''.$mode.'\');'
			.PHP_EOL.'define(\'COMMONDIR\',\''.$cmddir.'\');'
			.PHP_EOL
	);
	
	if(!is_file($f=($cmddir.'/'.$mode.'.php'))){
		\ebi\Util::file_write($f,<<< '__SRC__'
<?php
\ebi\Conf::set([
	'ebi.Log'=>[
		'level'=>'warn',
		'file'=>dirname(__DIR__).'/work/output.log',
		'stdout'=>false,
	],
]);
__SRC__
		);
	}
	\cmdman\Std::println_success('Written: '.realpath($f));	
	\cmdman\Std::println_success('Written: '.realpath($settings_file));	
	\cmdman\Std::println_info('Application mode changed.');
	\cmdman\Std::println_danger('Not complete setup - please try again.');
	exit;
}else{
	\cmdman\Std::println_info('Application mode is `'.$mode.'`');
}
if(\cmdman\Std::read('setup .htaccess?','n',['y','n']) == 'y'){
	$base = \cmdman\Std::read('base path?','/'.basename(getcwd()));
	
	list($path,$rules) = \ebi\Dt::htaccess($base);
	\cmdman\Std::println_warning('Written '.realpath($path));
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
			$autoload_file = $p;
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
}else if(\cmdman\Std::read('getting started?','n',['y','n']) == 'y'){
	\ebi\Util::mkdir($path.'/lib/my');
	\ebi\Util::mkdir($path.'/resources/media');
	\ebi\Util::mkdir($path.'/resources/templates');
	\ebi\Util::copy(__DIR__.'/setup/lib/my/Calc.php',$path.'/lib/my/Calc.php');		
	\ebi\Util::copy(__DIR__.'/setup/templates/start.html',$path.'/resources/templates/start.html');
	\ebi\Util::copy(__DIR__.'/setup/templates/days.html',$path.'/resources/templates/days.html');
	\ebi\Util::copy(__DIR__.'/setup/templates/calc.html',$path.'/resources/templates/calc.html');
	\ebi\Util::copy(__DIR__.'/setup/index.php',$path.'/index.php');
}
if(is_file($f=\ebi\Dt::setup_file())){
	\cmdman\Std::println_info('Run setup.');
	\ebi\Dt::setup();
}
\cmdman\Std::println_info('Done.');

