<?php
/**
 * data export
 * @param string $file
 */
if(empty($file)){
	$file = getcwd().'/dao.dump';
}
foreach(\ebi\Dt::export_dao_dump($file) as $f => $cnt){
	\cmdman\Std::println_info('Export '.$f.' ('.$cnt.')');	
}

\cmdman\Std::println_success(PHP_EOL.'Writen: '.$file);


