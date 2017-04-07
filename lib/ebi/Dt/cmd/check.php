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
		\cmdman\Std::println_success('OK   '.$class_name);
	}catch(\ebi\exception\NotFoundException $e){
		\cmdman\Std::println_success('OK   '.$class_name);
	}catch(\ebi\exception\ConnectionException $e){
		$failure['db']++;
		\cmdman\Std::println_danger('Fail '.$class_name.': '.$e->getMessage());
	}catch(\Exception $e){
		$failure['db']++;
		\cmdman\Std::println_warning('Fail '.$class_name.': '.$e->getMessage());
	}
}

\cmdman\Std::println();
\cmdman\Std::println_info('Entry (mapping):');

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
					
					\cmdman\Std::println_success('OK   '.$entry.' '.$p['name']);
				}catch(\ReflectionException $e){
					$failure['entry']++;
					\cmdman\Std::println_danger('Fail '.$entry.' '.$p['name']);
				}
			}
		}
	}
}


\cmdman\Std::println();
\cmdman\Std::println_info('Config:');

foreach(\ebi\Dt::classes() as $info){
	$class_info = \ebi\Dt\Man::class_info($info['class']);

	if($class_info->has_opt('config_list')){
		foreach($class_info->opt('config_list') as $info){
			$key = '\\'.$class_info->name().'@'.$info->name();
			
			if($info->opt('def')){
				cmdman\Std::println_success('x '.$key);
			}else{
				cmdman\Std::println_info('- '.$key);
			}
		}
	}
}





\cmdman\Std::println();

if(empty($failure['db']) && empty($failure['entry'])){
	\cmdman\Std::println_success('Success');
}else{
	\cmdman\Std::println_danger('Failure: '.
		'Database('.(!empty($failure['db']) ? $failure['db'] : '0').') '.
		'Entry('.(!empty($failure['entry']) ? $failure['entry'] : '0').') '
	);
}


