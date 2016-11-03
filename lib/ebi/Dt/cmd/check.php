<?php
/**
 * Check
 */

\cmdman\Std::println_info('Database:');

foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
	$class_name = \ebi\Util::get_class_name($class_info['class']);
	
	try{
		call_user_func([$class_name,'find_get']);
		
		\cmdman\Std::println_success(' '.$class_name.' OK');
	}catch(\ebi\exception\NotFoundException $e){
	}catch(\ebi\exception\ConnectionException $e){
		\cmdman\Std::println_danger(' '.$class_name.' Failure, '.$e->getMessage());
	}catch(\Exception $e){
		\cmdman\Std::println_warning(' '.$class_name.' Failure, '.$e->getMessage());
	}
}
