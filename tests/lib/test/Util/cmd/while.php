<?php

$func = function(){
	$i = 0;
	while(true){
		$i++;
		var_dump(number_format($i).': '.memory_get_peak_usage());
		yield $i;
	}
};

foreach($func() as $i){
	
}

