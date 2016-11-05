<?php
/**
 * Check
 */

$failure = ['db'=>0,'entry'=>0];

\cmdman\Std::println_info('Database:');

foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
	$class_name = \ebi\Util::get_class_name($class_info['class']);
	
	try{
		call_user_func([$class_name,'find_get']);
		
		\cmdman\Std::println_success(' '.$class_name.' OK');
	}catch(\ebi\exception\NotFoundException $e){
	}catch(\ebi\exception\ConnectionException $e){
		$failure['db']++;
		\cmdman\Std::println_danger(' '.$class_name.' Failure, '.$e->getMessage());
	}catch(\Exception $e){
		$failure['db']++;
		\cmdman\Std::println_warning(' '.$class_name.' Failure, '.$e->getMessage());
	}
}

\cmdman\Std::println();
\cmdman\Std::println_info('Entry:');

foreach(\ebi\Util::ls(getcwd()) as $f){
	$src = file_get_contents($f->getPathname());
	
	if(strpos($src,'\ebi\Flow::app(') !== false){
		$map = \ebi\Flow::get_map($f->getPathname());
		$entry = str_replace(getcwd(),'',$f->getPathname());

		foreach($map['patterns'] as $p){
			if(array_key_exists('action',$p) && is_string($p['action'])){
				try{
					list($c,$m) = explode('::',$p['action']);
					$mr = new \ReflectionMethod(\ebi\Util::get_class_name($c),$m);
					
					\cmdman\Std::println_success($entry.' '.$p['name'].' OK');
				}catch(\ReflectionException $e){
					$failure['entry']++;
					\cmdman\Std::println_danger($entry.' '.$p['name'].' Failure');
				}
			}
		}
	}
}
\cmdman\Std::println();
\cmdman\Std::println_danger('Failure: '.
	(!empty($failure['db']) ? 'Database('.$failure['db'].') ' : '').
	(!empty($failure['entry']) ? 'Entry('.$failure['entry'].') ' : '')
);
		

