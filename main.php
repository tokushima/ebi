<?php
set_error_handler(function($n,$s,$f,$l){
	throw new \ErrorException($s,0,$n,$f,$l);
});

ini_set('html_errors',0);
ini_set('error_reporting',E_ALL);
ini_set('xdebug.overload_var_dump',0);
ini_set('xdebug.var_display_max_children',-1);
ini_set('xdebug.var_display_max_data',-1);
ini_set('xdebug.var_display_max_depth',-1);

date_default_timezone_set('Asia/Tokyo');
mb_language('Japanese');
mb_internal_encoding('UTF-8');
ini_set('default_charset','UTF-8');

if(is_file($f=(getcwd().'/__settings__.php'))){
	include_once($f);
}
if(!defined('COMMONDIR') && is_dir(getcwd().'/commons')){
	define('COMMONDIR',getcwd().'/commons');
}
if(!defined('APPMODE')){
	define('APPMODE','local');
}
if(defined('COMMONDIR')){
	if(is_file($f=(constant('COMMONDIR').'/common.php'))){
		include_once($f);
	}
	if(is_file($f=(constant('COMMONDIR').'/'.constant('APPMODE').'.php'))){
		include_once($f);
	}
}
if(!defined('CMDMAN_ERROR_CALLBACK')){
	define('CMDMAN_ERROR_CALLBACK','\\ebi\\Log::error');
}

