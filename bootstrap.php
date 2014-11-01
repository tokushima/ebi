<?php
$dir = dirname(dirname(dirname(__DIR__))).'/lib';
if(is_dir($dir) && strpos(get_include_path(),$dir) === false){
	set_include_path($dir.PATH_SEPARATOR.get_include_path());
}
if(is_dir($libdir=dirname(dirname(dirname(__DIR__))).'/pear') && strpos(get_include_path(),$libdir) === false){
	set_include_path(get_include_path().PATH_SEPARATOR.$libdir);
}

include_once(__DIR__.'/autoload.php');

