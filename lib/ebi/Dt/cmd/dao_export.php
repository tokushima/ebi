<?php
/**
 * Data export
 * @param string $file
 */
if(empty($file)){
	$file = getcwd().'/dao.dump';
}
\ebi\Util::file_write($file,'');

foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
	$class_name = \ebi\Util::get_class_name($class_info['class']);
	
	$cnt = 0;
	foreach(call_user_func([$class_name,'find']) as $obj){
		\ebi\Util::file_append(
			$file,
			json_encode([
				'model'=>$class_name,
				'data'=>$obj->props()
			]
		).PHP_EOL);
		$cnt++;
	}
	\cmdman\Std::println_info('Export '.$class_name.' ('.$cnt.')');
	
}
\cmdman\Std::println_success(PHP_EOL.'Writen: '.$file);
