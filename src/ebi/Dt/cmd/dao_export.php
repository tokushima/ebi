<?php
/**
 * dao data export
 * @param string $file
 */
if(empty($file)){
	$file = getcwd().'/dao.dump';
}
foreach(\ebi\Dt::export_dao_dump($file) as $f => $cnt){
	\ebico\Std::println_info('Export '.$f.' ('.$cnt.')');	
}

\ebico\Std::println_success(PHP_EOL.'Writen: '.$file);


