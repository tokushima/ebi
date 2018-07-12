<?php
/**
* Dao import / export / show creata tbale
*/

$model_list = [];
foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
	$model_list[] = \ebi\Util::get_class_name($class_info['class']);
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
		\cmdman\Std::println('usage: [import <input file>] [export <output file>] [show <connector>]');
}

