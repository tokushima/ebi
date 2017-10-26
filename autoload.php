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

include_once(__DIR__.'/boot.php');
