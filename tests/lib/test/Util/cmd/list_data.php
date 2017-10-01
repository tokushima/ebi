<?php
use \ebi\Q;

$path = \ebi\Conf::work_path('list_data.csv');
\ebi\Util::file_write($path,'');

$i = 0;
$list = [];
$unit = 'B';

foreach(\test\db\Data::find(Q::gt('num',50)) as $obj){
	$i++;
	$list[] = sprintf('%s,%s,%s,%s',$obj->id(),$obj->num(),$obj->val1(),$obj->val2());
	
	if($i % 1000 === 0){
		$mem = memory_get_peak_usage();
		
		if($mem > (1024 * 1024 * 1024)){
			$mem = round($mem / (1024 * 1024 * 1024),3);
			$unit = ' GB';
		}else if($mem > (1024 * 1024)){
			$mem = round($mem / (1024 * 1024),3);
			$unit = 'MB';
		}else if($mem > 1024){
			$mem = round($mem / (1024),3);
			$unit = 'KB';
		}
		
		\cmdman\Std::backspace(100);
		print('Cnt. '.number_format($i).', Mem: '.$mem.' '.$unit);
		\ebi\Util::file_append($path,implode(PHP_EOL,$list));
		
		$list = [];
	}
}
\ebi\Util::file_append($path,implode(PHP_EOL,$list));


