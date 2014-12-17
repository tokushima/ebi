<?php
/**
 * モデルからtableを作成する
 * @param string $model 
 * @param boolean $drop 
 */

$model_list = [];

foreach(\ebi\Dt::classes('\ebi\Dao') as $class_info){
	$r = new \ReflectionClass($class_info['class']);
	if($r->getParentClass()->getName() == 'ebi\Dao'){
		$model_list[] = $r->getName();
	}
}

$model = str_replace('.','\\',$model);

foreach($model_list as $m){
	if(empty($model) || strpos($m,$model) !== false){
		if($drop === true && call_user_func([$m,'drop_table'])){
			print('dropped '.$m.PHP_EOL);
		}
		if(call_user_func([$m,'create_table'])){
			print('created '.$m.PHP_EOL);
		}
	}
}
