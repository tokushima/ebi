<?php
/**
 * Create table
 * @param string $model 
 * @param boolean $drop 
 */

$model_list = [];

if(empty($model)){
	foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
		$model_list[] = \ebi\Util::get_class_name($class_info['class']);
	}
}else{
	$model_list[] = \ebi\Util::get_class_name($model);
}

foreach($model_list as $m){
	if($drop === true){
		call_user_func([$m,'drop_table']);
		\cmdman\Std::println('dropped '.$m);
	}
	call_user_func([$m,'create_table']);
	\cmdman\Std::println('created '.$m);
}