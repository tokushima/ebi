<?php
/**
 * dao data export
 * @param string $file
 */
if(empty($file)){
	$file = getcwd().'/export.dat';
}
foreach(\ebi\Dt::classes('\ebi\Dao') as $class_info){
	$r = new \ReflectionClass($class_info['class']);
	
	if($r->getParentClass()->getName() == 'ebi\Dao'){
		\ebi\Util::file_append($file,'[['.$r->getName().']]'.PHP_EOL);

		foreach(call_user_func([$r->getName(),'find']) as $obj){
			\ebi\Util::file_append($file,json_encode($obj->props()).PHP_EOL);
		}
	}
}
