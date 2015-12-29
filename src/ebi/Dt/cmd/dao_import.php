<?php
/**
 * dao data import
 * @param string $file
 */
\ebi\Dt::create_table();

if(empty($file)){
	$file = getcwd().'/dao.dump';
}
\cmdman\Std::println_success('Load '.$file);

$rtn = \ebi\Dt::import_dao_dump($file);

foreach($rtn['update'] as $class => $cnt){
	if(!isset($invalid[$class])){
		\cmdman\Std::println_info(' Import '.$class.' ('.$cnt[0].')');
	}
}
foreach($rtn['invalid'] as $class => $v){
	\cmdman\Std::println_info(' Fail '.$class);
}
							

