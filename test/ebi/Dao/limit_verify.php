<?php
\test\db\LimitVerify::create_table();
\test\db\LimitVerify::find_delete();


$obj = new \test\db\LimitVerify();
$obj->value1('123');
$obj->value2(3);
$obj->save();

$obj = new \test\db\LimitVerify();
$obj->value1('1234');
$obj->value2(4);
try{
	$obj->save();
	failure();
}catch(\ebi\Exception $e){
}


$obj = new \test\db\LimitVerify();
$obj->value1('1');
$obj->value2(1);
try{
	$obj->save();
	failure();
}catch(\ebi\Exception $e){
}

$obj = new \test\db\LimitVerify();
$obj->save();
