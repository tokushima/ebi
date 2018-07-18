<?php
/**
 * Dao create / drop / import / export / show creata tbale
 * @param string[] $model target models
 */


$model_list = [];

if(empty($model)){
	foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
		$model_list[] = \ebi\Util::get_class_name($class_info['class']);
	}
}else{
	foreach($model as $m){
		$ls = false;
		
		if(substr($m,-1) == '*'){
			$m = substr($m,0,-1);
			$ls = true;
		}
		$class_name = \ebi\Util::get_class_name($m);
		
		if(!is_subclass_of($class_name,\ebi\Dao::class)){
			throw new \ebi\exception\InvalidArgumentException();
		}
		$model_list[] = $class_name;
		
		if($ls){
			foreach(\ebi\Util::ls_classes($class_name,\ebi\Dao::class) as $cn){
				$model_list[] = $cn;
			}
		}
	}
}

$values = \cmdman\Args::values();
$values = empty($values) ? [''] : $values;
$cmd = array_shift($values);

switch($cmd){
	case 'show':
		$connector = empty($values) ? 'ebi.SqliteConnector' : array_shift($values);
		
		foreach($model_list as $m){
			$dao = (new \ReflectionClass($m))->newInstance();
			
			$connector_inst = \ebi\Util::strtoinstance($connector);
			print($connector_inst->create_table_sql($dao).PHP_EOL);
		}
		break;
	case 'create':
		foreach($model_list as $m){
			if(call_user_func([$m,'create_table'])){
				\cmdman\Std::println_success('create table '.$m);
			}else{
				\cmdman\Std::println_info('ignore '.$m);
			}
		}
		break;
	case 'drop':		
		foreach($model_list as $m){
			if(call_user_func([$m,'drop_table'])){
				\cmdman\Std::println_success('drop table '.$m);
			}else{
				\cmdman\Std::println_info('ignore '.$m);
			}
		}
		break;
	case 'export':
		$file = empty($values) ? getcwd().'/dao.dump' : array_shift($values);
		\ebi\Util::file_write($file,'');
		
		foreach($model_list as $class_name){
			$cnt = 0;
			foreach(call_user_func([$class_name,'find']) as $obj){
				\ebi\Util::file_append(
						$file,
						json_encode([
							'model'=>$class_name,
							'data'=>$obj->props()
						]
				).PHP_EOL);
				$cnt++;
			}
			\cmdman\Std::println_info('Export '.$class_name.' ('.$cnt.')');
		}
		\cmdman\Std::println_success(PHP_EOL.'Writen: '.$file);
		break;
	case 'import':
		$file = empty($values) ? getcwd().'/dao.dump' : array_shift($values);
		
		if(!is_file($file)){
			\cmdman\Std::println_danger('usage: php cmdman.phar  ebi.Dt::dao import <input file>');
			exit;
		}
		$get_dao_dump_func = function($file){
			$fp = fopen($file,'rb');
			
			$i = 0;
			$line = '';
			
			while(!feof($fp)){
				$i++;
				$line .= fgets($fp);
				
				if(!empty($line)){
					$arr = json_decode($line,true);
					
					if($arr !== false){
						if(!isset($arr['model']) || !isset($arr['data'])){
							throw new \ebi\exception\InvalidArgumentException('Invalid line '.$i);
						}
						yield $arr;
						
						$line = '';
					}
				}
			}
		};
		
		$update = $failure = [];
		foreach($get_dao_dump_func($file) as $arr){
			$ref = new \ReflectionClass($arr['model']);
			$inst = $ref->newInstance();
			
			if(!array_key_exists($ref->getName(),$failure)){
				try{
					foreach($inst->props() as $k => $v){
						if(array_key_exists($k,$arr['data'])){
							if($inst->prop_anon($k,'cond') == null && $inst->prop_anon($k,'extra',false) === false){
								$inst->prop_anon($k,'auto_now',false,true);
								call_user_func_array([$inst,$k],[$arr['data'][$k]]);
							}
						}
					}
					$inst->save();
					
					if(!array_key_exists($ref->getName(),$update)){
						$update[$ref->getName()] = 0;
					}
					$update[$ref->getName()]++;
				}catch(\ebi\exception\BadMethodCallException $e){
					$failure[$ref->getName()] = true;
				}
			}
		}
		foreach($update as $class_name => $cnt){
			\cmdman\Std::println_info(' Import '.$class_name.' ('.$cnt.')');
		}
		foreach(array_keys($failure) as $class_name){
			\cmdman\Std::println_danger(' Failure '.$class_name);
		}
		break;
	default:
		\cmdman\Std::println('usage: [create <model> ... ] [drop <model> ... ] [import <input file>] [export <output file>] [show <connector>]');
}

