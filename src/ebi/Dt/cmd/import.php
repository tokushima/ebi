<?php
/**
 * dao data import
 * @param string $file
 */
\ebi\Dt::create_table();

if(empty($file)){
	$file = getcwd().'/dump.ddj';
}
$dao_list = [];
foreach(\ebi\Dt::classes('\ebi\Dao') as $class_info){
	$r = new \ReflectionClass($class_info['class']);
	
	if($r->getParentClass()->getName() == 'ebi\Dao'){
		$dao_list[] = $r->getName();
	}
}

$current = null;

$fp = fopen($file,'rb');
while(!feof($fp)){
	$line = fgets($fp);
	if(!empty($line)){
		if($line[0] == '['){
			$current = null;
			$class = preg_replace('/\[\[(.+)\]\]/','\\1',trim($line));
			
			if(in_array($class, $dao_list)){
				$current = (new \ReflectionClass($class))->newInstance();
			}
		}else if($line[0] == '{' && !empty($current)){
			$obj = clone($current);
			$arr = json_decode($line,true);
			
			foreach($obj->props() as $k => $v){
				if(array_key_exists($k,$arr)){
					call_user_func_array([$obj,$k],[$arr[$k]]);
				}
			}
			$obj->save();
		}
	}
}


