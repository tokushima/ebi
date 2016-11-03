<?php
/**
 * Data import
 * @param string $file
 */

$drop = false;
$model = null;
include(__DIR__.'/create_table.php');

if(empty($file)){
	$file = getcwd().'/dao.dump';
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

