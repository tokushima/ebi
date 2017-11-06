<?php
/**
 * Average benchmark
 * @param string $path benchmark file
 * @param string $filter text filter
 */

$data = [];
foreach(\ebi\Util::file_read_csv($path,"\t") as $line){
	list($path,$time,$mem,$peak) = $line;

	if(empty($filter) || preg_match('@'.$filter.'@',$path)){
		if(isset($data[$path])){
			$data[$path] = [$data[$path][0] + $time,$data[$path][1] + $mem,$data[$path][2] + $peak,$data[$path][3] + 1];
		}else{
			$data[$path] = [$time,$mem,$peak,1];
		}
	}
}


$avg = [];
foreach($data as $path => $d){	
	$avg[$path] = [
		str_pad(round((float)$d[0] / (float)$d[3],4),6),
		ceil((float)$d[1] / (float)$d[3]),
		ceil((float)$d[2] / (float)$d[3]),
		$d[3],
		$path,
	];
}

$len = [4,3,8,5,5];
foreach($avg as $path => $d){
	for($i=0;$i<=4;$i++){
		$len[$i] = (strlen($d[$i]) > $len[$i]) ? strlen($d[$i]) : $len[$i];
	}
}


$head = ['Time','Mem','Peak Mem','Count','Path'];
for($i=0;$i<=4;$i++){
	\cmdman\Std::p(str_pad($head[$i], $len[$i],' ',STR_PAD_RIGHT).'    ','36');
}
print(PHP_EOL);

foreach($avg as $path => $d){
	for($i=0;$i<=3;$i++){
		print(str_pad($d[$i], $len[$i],' ',STR_PAD_LEFT).'    ');
	}
	print($d[4].PHP_EOL);
}



