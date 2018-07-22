<?php
$vars = \ebi\Util::parse_numbers('1,2,5,100,-10,1000..1002');

eq([1,2,5,100,-10,1000,1001,1002],$vars);


// duplication
$vars = \ebi\Util::parse_numbers('1,2,5,2,100,-10,1000..1002, 1001..1004');

eq([1,2,5,100,-10,1000,1001,1002,1003,1004],$vars);



// not number
try{
	$vars = \ebi\Util::parse_numbers('1,2,5,100,A,-10,1000..1002');
	fail();
}catch(\ebi\exception\IllegalDataTypeException $e){	
}

