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
\cmdman\Std::println_success('Load '.$file);



foreach(\ebi\Dt::get_dao_dump($file) as $arr){
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
}

foreach($update as $class => $cnt){
	if(!isset($invalid[$class])){
		\cmdman\Std::println_info(' Import '.$class.' ('.$cnt[0].')');
	}
}
foreach($invalid as $class => $v){
	\cmdman\Std::println_info(' Fail '.$class);
}
							

