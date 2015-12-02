<?php
$run = \ebico\Std::read('install','n',['y','n']);
if($run == 'y'){
	\ebico\Std::println_success('install yes');
}else{
	\ebico\Std::println_success('install no');	
}

