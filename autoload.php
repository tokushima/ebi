<?php
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
if(ini_get('date.timezone') == ''){
	date_default_timezone_set('Asia/Tokyo');
}
if(extension_loaded('mbstring')){
	if('neutral' == mb_language()) mb_language('Japanese');
	mb_internal_encoding('UTF-8');
}

$dir = getcwd();
if(is_file($f=($dir.'/__settings__.php'))){
	include_once($f);
}
if(is_file($f=($dir.'/test/testman.phar'))){
	include_once($f);
}
if(!defined('COMMONDIR') && is_dir($dir.'/commons')){
	define('COMMONDIR',$dir.'/commons');
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


