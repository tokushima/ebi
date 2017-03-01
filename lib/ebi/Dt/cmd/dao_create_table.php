<?php
/**
 * Create table
* @param string $model 対象のDao
* @param boolean $drop 削除して作成する
* @param boolean $show CREATE TABLEを表示する(作成はしない)
* @param string $connector CREATE TABLEのSQLを発行するコネクタを指定する(show時のみ)
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
	if($show === true){
		$dao = (new \ReflectionClass($m))->newInstance();

		if(!empty($connector)){
			$connector_inst = \ebi\Util::strtoinstance($connector);
			print($connector_inst->create_table_sql($dao).PHP_EOL);
		}else{
			$con = \ebi\Dao::connection(get_class($dao));
			print($con->connector()->create_table_sql($dao).PHP_EOL);
		}
	}else{
		if($drop === true){
			if(call_user_func([$m,'drop_table'])){
				\cmdman\Std::println_danger('drop table '.$m);
			}
		}
		if(call_user_func([$m,'create_table'])){
			\cmdman\Std::println_success('create table '.$m);
		}else{
			\cmdman\Std::println_info('ignore '.$m);
		}
	}
}
