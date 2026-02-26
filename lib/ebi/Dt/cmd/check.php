<?php
/**
 * Check
 * @param boolean $verbose
 */

$failure = ['db'=>0,'entry'=>0,'mail'=>0,'conf'=>0];

// Database
$db_passed = [];
$db_errors = [];
foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
	$r = new \ReflectionClass($class_info['class']);
	if($r->isAbstract()){
		continue;
	}
	$class_name = \ebi\Util::get_class_name($class_info['class']);
	try{
		call_user_func([$class_name,'find_get']);
		$db_passed[] = $class_name;
	}catch(\ebi\exception\NotFoundException $e){
		$db_passed[] = $class_name;
	}catch(\ebi\exception\ConnectionException $e){
		$failure['db']++;
		$db_errors[] = ['danger', $class_name.': '.$e->getMessage()];
	}catch(\Exception $e){
		$failure['db']++;
		$db_errors[] = ['warning', $class_name.': '.$e->getMessage()];
	}
}

\cmdman\Std::println();
if(empty($db_errors)){
	\cmdman\Std::println_success('[Database] '.count($db_passed).' passed');
}else{
	\cmdman\Std::println_danger('[Database] '.count($db_passed).' passed, '.$failure['db'].' failed');
	foreach($db_errors as $err){
		if($err[0] === 'danger'){
			\cmdman\Std::println_danger('  [NG] '.$err[1]);
		}else{
			\cmdman\Std::println_warning('  [NG] '.$err[1]);
		}
	}
}
if($verbose){
	foreach($db_passed as $name){
		\cmdman\Std::println_success('  [OK] '.$name);
	}
}

// Endpoints
$entry_passed = [];
$entry_errors = [];
foreach(\ebi\Util::ls(getcwd(),false,'/\.php$/') as $f){
	$src = file_get_contents($f->getPathname());

	if(strpos($src,'\ebi\Flow::app(') !== false || strpos($src,'\ebi\App::app(') !== false || strpos($src,'\ebi\App::run(') !== false){
		$map = \ebi\App::get_map($f->getPathname());
		$entry = str_replace(getcwd(),'',$f->getPathname());

		foreach($map['patterns'] as $p){
			if(array_key_exists('action',$p) && is_string($p['action'])){
				try{
					[$c, $m] = explode('::',$p['action']);
					$c = \ebi\Util::get_class_name($c);

					new \ReflectionMethod($c,$m);
					$entry_passed[] = $entry.' '.$p['name'];
				}catch(\ReflectionException $e){
					$failure['entry']++;
					$entry_errors[] = $entry.' '.$p['name'];
				}
			}
		}
	}
}

\cmdman\Std::println();
if(empty($entry_errors)){
	\cmdman\Std::println_success('[Endpoints] '.count($entry_passed).' passed');
}else{
	\cmdman\Std::println_danger('[Endpoints] '.count($entry_passed).' passed, '.$failure['entry'].' failed');
	foreach($entry_errors as $err){
		\cmdman\Std::println_danger('  [NG] '.$err);
	}
}
if($verbose){
	foreach($entry_passed as $name){
		\cmdman\Std::println_success('  [OK] '.$name);
	}
}

// Config
$conf_passed = [];
$conf_undefined_count = 0;
$conf_errors = [];
$find_defined_classes = [];
foreach(\ebi\Dt::classes() as $info){
	$class_info = \ebi\Dt\SourceAnalyzer::class_info($info['class']);

	if($class_info->has_opt('config_list')){
		foreach($class_info->opt('config_list') as $info){
			$key = $class_info->name().'@'.$info->name();

			if(!isset($find_defined_classes[$class_info->name()])){
				$find_defined_classes[$class_info->name()] = [];
			}
			$find_defined_classes[$class_info->name()][] = $info->name();

			if($info->opt('def')){
				$conf_passed[] = $key;
			}else{
				$conf_undefined_count++;
			}
		}
	}
}

foreach(\ebi\Conf::get_defined_keys() as $class => $keys){
	if(!isset($find_defined_classes[$class])){
		try{
			$class_info = \ebi\Dt\SourceAnalyzer::class_info($class,true);

			$find_defined_classes[$class] = [];
			foreach($class_info->opt('config_list') as $info){
				$find_defined_classes[$class][] = $info->name();
			}
		}catch(\Exception $e){
			$failure['conf']++;
			$conf_errors[] = ['danger', $class];
		}
	}
	if(isset($find_defined_classes[$class])){
		foreach($keys as $key){
			if(!in_array($key, $find_defined_classes[$class])){
				if($key !== 'handler'){
					$failure['conf']++;
					$conf_errors[] = ['warning', $class.'@'.$key];
				}
			}
		}
	}
}

\cmdman\Std::println();
$conf_summary = count($conf_passed).' passed';
if($conf_undefined_count > 0) $conf_summary .= ', '.$conf_undefined_count.' undefined';
if(!empty($conf_errors)) $conf_summary .= ', '.$failure['conf'].' failed';

if(empty($conf_errors)){
	\cmdman\Std::println_success('[Config] '.$conf_summary);
}else{
	\cmdman\Std::println_danger('[Config] '.$conf_summary);
	foreach($conf_errors as $err){
		if($err[0] === 'danger'){
			\cmdman\Std::println_danger('  [NG] '.$err[1]);
		}else{
			\cmdman\Std::println_warning('  [NG] '.$err[1]);
		}
	}
}
if($verbose){
	foreach($conf_passed as $key){
		\cmdman\Std::println_success('  [OK] '.$key);
	}
}

// Mail
$template_list = \ebi\Dt\SourceAnalyzer::mail_template_list();
$mail_passed = [];
$mail_errors = [];
$class_name_max_length = 0;
foreach(\ebi\Dt::classes() as $class){
	$class_src = \ebi\Util::file_read($class['filename']);

	foreach($template_list as $mail_info){
		if(preg_match('/[^\w\/_]'.preg_quote($mail_info->name(),'/').'/',$class_src)){
			if(\ebi\Dt\SourceAnalyzer::find_mail_doc($mail_info, $class_src)){
				if(!empty($mail_info->opt('undefined_vars'))){
					$failure['mail']++;
					$mail_errors[] = [
						$class['class'],
						'['.$mail_info->opt('x_t_code').'] '.$mail_info->name(),
						$mail_info->opt('undefined_vars')
					];
				}else{
					$mail_passed[] = [
						$class['class'],
						'['.$mail_info->opt('x_t_code').'] '.$mail_info->name()
					];
				}

				if($class_name_max_length < strlen($class['class'])){
					$class_name_max_length = strlen($class['class']);
				}
			}
		}
	}
}

\cmdman\Std::println();
if(empty($mail_errors)){
	\cmdman\Std::println_success('[Mail] '.count($mail_passed).' passed');
}else{
	\cmdman\Std::println_danger('[Mail] '.count($mail_passed).' passed, '.$failure['mail'].' failed');
	foreach($mail_errors as $result){
		\cmdman\Std::println_danger('  [NG] '.str_pad($result[0],$class_name_max_length + 2).$result[1].' [ '.implode(', ',$result[2]).' ]');
	}
}
if($verbose){
	foreach($mail_passed as $result){
		\cmdman\Std::println_success('  [OK] '.str_pad($result[0],$class_name_max_length + 2).$result[1]);
	}
}


\cmdman\Std::println();
\cmdman\Std::println_info(str_repeat('=',50));

$total_passed = count($db_passed) + count($entry_passed) + count($conf_passed) + count($mail_passed);
$total_failed = $failure['db'] + $failure['entry'] + $failure['mail'] + $failure['conf'];

if($total_failed === 0){
	\cmdman\Std::println_success('  All checks passed ('.$total_passed.' total)');
}else{
	$failure_details = [];
	if(!empty($failure['db'])) $failure_details[] = 'Database('.$failure['db'].')';
	if(!empty($failure['entry'])) $failure_details[] = 'Endpoints('.$failure['entry'].')';
	if(!empty($failure['mail'])) $failure_details[] = 'Mail('.$failure['mail'].')';
	if(!empty($failure['conf'])) $failure_details[] = 'Conf('.$failure['conf'].')';

	\cmdman\Std::println_danger('  '.$total_failed.' failed, '.$total_passed.' passed');
	\cmdman\Std::println_danger('  '.implode(', ', $failure_details));
}

\cmdman\Std::println_info(str_repeat('=',50));
\cmdman\Std::println();
