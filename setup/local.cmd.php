<?php
$run = \cmdman\Std::read('install','n',['y','n']);
if($run == 'y'){
	\cmdman\Std::println_success('install yes');
}else{
	\cmdman\Std::println_success('install no');	
}

