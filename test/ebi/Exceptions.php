<?php
\ebi\Exceptions::add(new \LogicException('AAA'));

try{
	\ebi\Exceptions::throw_over();
	failure('例外でるはず');
}catch(\ebi\Exceptions $e){
	$i = 0;
	foreach($e as $g => $exception){
		$i++;
	}
	eq(1,$i);
}
