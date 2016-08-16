<?php
if(is_dir(getcwd().'/lib')){
	set_include_path(get_include_path().PATH_SEPARATOR.getcwd().'/lib');
}
spl_autoload_register(function($c){
	if(!empty($c)){
		$cp = str_replace('\\','/',(($c[0] == '\\') ? substr($c,1) : $c));
		
		foreach(explode(PATH_SEPARATOR,get_include_path()) as $p){
			if(!empty($p) && ($r = realpath($p)) !== false){
				if(is_file($f=($r.'/'.str_replace('_','/',$cp).'.php'))
					|| is_file($f=($r.'/'.implode('/',array_slice(explode('_',$cp),0,-1)).'.php'))
				){
					require_once($f);

					if(class_exists($c,false) || interface_exists($c,false) || trait_exists($c,false)){
						return true;
					}
				}
			}
		}
	}
	return false;
},true,false);

set_error_handler(function($n,$s,$f,$l){
	throw new \ErrorException($s,0,$n,$f,$l);
});

ini_set('display_errors','On');
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
if(\ebi\Conf::exists('ebi.Conf','timezone')){
	$timezone = \ebi\Conf::get('ebi.Conf@timezone');
	
	if(date_default_timezone_set($timezone) === false){
		throw new \InvalidArgumentException($timezone.' is not supported');
	}
}
if(\ebi\Conf::exists('ebi.Conf','language')){
	$language = \ebi\Conf::get('ebi.Conf@language');
	
	if(mb_language($language) === false){
		throw new \InvalidArgumentException($language.' is not supported');
	}
}
if(!defined('CMDMAN_ERROR_CALLBACK')){
	define('CMDMAN_ERROR_CALLBACK','\\ebi\\Log::error');
}

