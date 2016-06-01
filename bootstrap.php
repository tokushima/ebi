<?php
$dir = dirname(dirname(dirname(__DIR__))).'/lib';
if(is_dir($dir) && strpos(get_include_path(),$dir) === false){
	set_include_path($dir.PATH_SEPARATOR.get_include_path());
}

include_once(__DIR__.'/autoload.php');

