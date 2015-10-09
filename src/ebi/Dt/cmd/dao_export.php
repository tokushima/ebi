<?php
/**
 * dao data export
 * @param string $file
 */
if(empty($file)){
	$file = getcwd().'/dao.dump';
}
\ebi\Util::file_write($file,'');

foreach(\ebi\Dt::classes('\ebi\Dao') as $class_info){
	$r = new \ReflectionClass($class_info['class']);
	$cnt = 0;
	
	if($r->getParentClass()->getName() == 'ebi\Dao'){
		foreach(call_user_func([$r->getName(),'find']) as $obj){
			\ebi\Util::file_append($file,json_encode(['model'=>$r->getName(),'data'=>$obj->props()]).PHP_EOL);
			$cnt++;
		}
	}
	if(!empty($cnt)){
		\cmdman\Std::println_info('Export '.$r->getName().' ('.$cnt.')');
	}
}
\cmdman\Std::println_success(PHP_EOL.'Writen: '.$file);


