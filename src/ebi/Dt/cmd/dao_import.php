<?php
/**
 * dao data import
 * @param string $file
 */
\ebi\Dt::create_table();

if(empty($file)){
	$file = getcwd().'/dao.dump';
}

$update = $invalid = [];
$fp = fopen($file,'rb');
\cmdman\Std::println_success('Load '.$file);

$i = 0;
$line = '';

while(!feof($fp)){
	$i++;
	$line .= fgets($fp);
	
	if(!empty($line)){
		$arr = json_decode($line,true);
		
		if($arr !== false){
			if(!isset($arr['model']) || !isset($arr['data']) || !class_exists($arr['model'])){
				throw new \ebi\exception\InvalidArgumentException('Invalid line '.$i);
			}
			$class = $arr['model'];
			if(!isset($invalid[$class])){
				$inst = (new \ReflectionClass($class))->newInstance();
				
				if(!isset($update[$class])){
					$update[$class] = [call_user_func([$class,'find_count']),0];
				}
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
					$update[$class][1]++;
				}catch(\ebi\exception\BadMethodCallException $e){
					$invalid[$class] = true;
				}
			}
			$line = '';
		}
	}
}

foreach($update as $class => $cnt){
	if(!isset($invalid[$class])){
		\cmdman\Std::println_info(' Import '.$class.' ('.$cnt[0].')');
	}
}
foreach($invalid as $class => $v){
	\cmdman\Std::println_info(' Fail '.$class);
}
							

