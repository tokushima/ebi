<?php
/**
 * Check
 */

$failure = ['db'=>0,'entry'=>0,'mail'=>0];

\cmdman\Std::println_info('Database (Check Existence):');

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
\cmdman\Std::println_info('Entry (Check mapping):');

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
\cmdman\Std::println_info('Config (Check the definition):');

foreach(\ebi\Dt::classes() as $info){
	$class_info = \ebi\Dt\Man::class_info($info['class']);

	if($class_info->has_opt('config_list')){
		foreach($class_info->opt('config_list') as $info){
			$key = '\\'.$class_info->name().'@'.$info->name();
			
			if($info->opt('def')){
				cmdman\Std::println_success('o '.$key);
			}else{
				cmdman\Std::println_info('- '.$key);
			}
		}
	}
}

\cmdman\Std::println();
\cmdman\Std::println_info('Mail (Check version):');


$mail_template = \ebi\Dt\Man::mail_template_list();
$class_list = [];

foreach(\ebi\Dt::classes() as $class_info){
	$class_src = \ebi\Util::file_read($class_info['filename']);
	
	foreach($mail_template as $mail_info){
		if(strpos($class_src,$mail_info->name()) !== false){
			$class_list[] = $class_info['class'];
			break;
		}
	}
}
foreach($class_list as $class){
	$ref_class = new \ReflectionClass($class);
			
	foreach($ref_class->getMethods() as $ref_method){
		$method_info = \ebi\Dt\Man::method_info($ref_class->getName(),$ref_method->getName(),true);
				
		foreach($method_info->opt('mail_list') as $x_t_code => $mmi){
			$label = $ref_class->getName().'::'.$ref_method->getName()
						.' ('.$method_info->version().') '
						.' .. ['.$x_t_code.'] '.$mmi->name().' ('.$mmi->version().')';
			
			if($mmi->version() == $method_info->version()){
				cmdman\Std::println_success(' OK '.$label);
			}else{
				$failure['mail']++;
				\cmdman\Std::println_danger(' NG '.$label);
			}
		}
	}
}

\cmdman\Std::println();

if(empty($failure['db']) && empty($failure['entry']) && empty($failure['mail'])){
	\cmdman\Std::println_success('Success');
}else{
	\cmdman\Std::println_danger('Failure: '.
		'Database('.(!empty($failure['db']) ? $failure['db'] : '0').') '.
		'Entry('.(!empty($failure['entry']) ? $failure['entry'] : '0').') '.
		'Mail('.(!empty($failure['mail']) ? $failure['mail'] : '0').') '
	);
}


