<?php
/**
 * Check
 */

$failure = ['db'=>0,'entry'=>0,'mail'=>0,'conf'=>0];

\cmdman\Std::println();
\cmdman\Std::println_info('Database (Check Existence):');
\cmdman\Std::println_info(str_repeat('-',50));

foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
	$class_name = \ebi\Util::get_class_name($class_info['class']);
	try{
		call_user_func([$class_name,'find_get']);
		\cmdman\Std::println_success(' o   '.$class_name);
	}catch(\ebi\exception\NotFoundException $e){
		\cmdman\Std::println_success(' o   '.$class_name);
	}catch(\ebi\exception\ConnectionException $e){
		$failure['db']++;
		\cmdman\Std::println_danger(' x '.$class_name.': '.$e->getMessage());
	}catch(\Exception $e){
		$failure['db']++;
		\cmdman\Std::println_warning(' x '.$class_name.': '.$e->getMessage());
	}
}


\cmdman\Std::println();
\cmdman\Std::println_info('Entry (Check mapping):');
\cmdman\Std::println_info(str_repeat('-',50));

foreach(\ebi\Util::ls(getcwd(),false,'/\.php$/') as $f){
	$src = file_get_contents($f->getPathname());
	
	if(strpos($src,'\ebi\Flow::app(') !== false){
		$map = \ebi\Flow::get_map($f->getPathname());
		$entry = str_replace(getcwd(),'',$f->getPathname());
		
		foreach($map['patterns'] as $p){
			if(array_key_exists('action',$p) && is_string($p['action'])){
				try{
					[$c, $m] = explode('::',$p['action']);
					$c = \ebi\Util::get_class_name($c);
					
					new \ReflectionMethod($c,$m);
					\cmdman\Std::println_success(' o   '.$entry.' '.$p['name']);
				}catch(\ReflectionException $e){
					$failure['entry']++;
					\cmdman\Std::println_danger(' x '.$entry.' '.$p['name']);
				}
			}
		}
	}
}


\cmdman\Std::println();
\cmdman\Std::println_info('Config (Check the definition):');
\cmdman\Std::println_info(str_repeat('-',50));

$find_defined_classes = [];
foreach(\ebi\Dt::classes() as $info){
	$class_info = \ebi\Dt\Man::class_info($info['class']);
	
	if($class_info->has_opt('config_list')){
		foreach($class_info->opt('config_list') as $info){
			$key = $class_info->name().'@'.$info->name();
			
			if(!isset($find_defined_classes[$class_info->name()])){
				$find_defined_classes[$class_info->name()] = [];
			}
			$find_defined_classes[$class_info->name()][] = $info->name();
			
			if($info->opt('def')){
				\cmdman\Std::println_success(' o '.$key);
			}else{
				\cmdman\Std::println_info(' - '.$key);
			}
		}
	}
}

\cmdman\Std::println();
foreach(\ebi\Conf::get_defined_keys() as $class => $keys){
	if(!isset($find_defined_classes[$class])){
		try{
			$class_info = \ebi\Dt\Man::class_info($class,true);
			
			$find_defined_classes[$class] = [];
			foreach($class_info->opt('config_list') as $info){
				$find_defined_classes[$class][] = $info->name();
			}
		}catch(\Exception $e){
			$failure['conf']++;
			\cmdman\Std::println_danger(' x '.$class);
		}
	}
	if(isset($find_defined_classes[$class])){
		foreach($keys as $key){
			if(!in_array($key, $find_defined_classes[$class])){
				$failure['conf']++;
				\cmdman\Std::println_warning(' x '.$class.'@'.$key);
			}
		}
	}
}

\cmdman\Std::println();
\cmdman\Std::println_info('Mail:');
\cmdman\Std::println_info(str_repeat('-',50));

$template_list = \ebi\Dt\Man::mail_template_list();
$mail_check_result = [];
$class_name_max_length = 0;
foreach(\ebi\Dt::classes() as $class){
	$class_src = \ebi\Util::file_read($class['filename']);
	
	foreach($template_list as $mail_info){
		if(preg_match('/[^\w\/_]'.preg_quote($mail_info->name(),'/').'/',$class_src)){
			if(\ebi\Dt\Man::find_mail_doc($mail_info, $class_src)){
				$mail_check_result[] = [
					$class['class'],
					'['.$mail_info->opt('x_t_code').'] '.$mail_info->name(),
					$mail_info->opt('undefined_vars')
				];
				
				if(!empty($mail_info->opt('undefined_vars'))){
					$failure['mail']++;
				}
				
				if($class_name_max_length < strlen($class['class'])){
					$class_name_max_length = strlen($class['class']);
				}
			}
		}
	}
}
foreach($mail_check_result as $result){
	if(empty($result[2])){
		cmdman\Std::println_success(' o '.str_pad($result[0],$class_name_max_length + 2).$result[1]);
	}else{
		cmdman\Std::println_danger(' x '.str_pad($result[0],$class_name_max_length + 2).$result[1].' [ '.implode(', ',$result[2]).' ]');
	}
}


\cmdman\Std::println();
\cmdman\Std::println();

if(empty($failure['db']) && 
	empty($failure['entry']) && 
	empty($failure['mail']) && 
	empty($failure['conf'])
){
	\cmdman\Std::println_success('Success');
}else{
	\cmdman\Std::println_danger('Failure: '.
		'Database('.$failure['db'].') '.
		'Entry('.$failure['entry'].') '.
		'Mail('.$failure['mail'].') '.
		'Conf('.$failure['conf'].') '
	);
}
\cmdman\Std::println();
