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


$map_action_method_list = [];

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
					list($c,$m) = explode('::',$p['action']);
					$c = \ebi\Util::get_class_name($c);
					
					new \ReflectionMethod($c,$m);
					$map_action_method_list[$c.'::'.$m] = [$c,$m];
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


foreach($map_action_method_list as $action){
	try{
		$method_info = \ebi\Dt\Man::method_info($action[0], $action[1], true, true);
		
		foreach($method_info->opt('mail_list') as $x_t_code => $mail){
			$mail_src = \ebi\Util::file_read(\ebi\Dt\Man::mail_template_path($mail->name()));
			$bool = true;
			
			if(preg_match_all('/\{\$([\w_]+)/', $mail_src,$m)){
				$varnames = $m[1];
				
				foreach($varnames as $k => $varname){
					foreach($mail->params() as $param){
						if($varname === $param->name()){
							unset($varnames[$k]);
						}
					}
				}
				$bool = empty($varnames);
			}
			
			$label = $method_info->name()
					.' ('.$method_info->version().') '
					.' .. ['.$x_t_code.'] '.$mail->name().' ('.$mail->version().')';
					
					if($bool){
				cmdman\Std::println_success(' o '.$label);
			}else{
				$failure['mail']++;
				\cmdman\Std::println_danger(' x '.$label.' [ '.implode(', ',$varnames).' ]');
			}
		}
	}catch(\Exception $e){
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


