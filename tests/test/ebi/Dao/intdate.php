<?php
\test\db\DateTime::create_table();
\test\db\DateTime::find_delete();

$obj = new \test\db\DateTime();
$obj->idate(20170101);
$obj->save();

$obj = new \test\db\DateTime();
$obj->idate('2017年01月01日');
$obj->save();

$obj = new \test\db\DateTime();
$obj->idate('2017年1月1日');
$obj->save();

$obj = new \test\db\DateTime();
$obj->idate('12017年10月10日');
$obj->save();


$obj = new \test\db\DateTime();
$obj->idate(120170101);
$obj->save();


try{
	$obj = new \test\db\DateTime();
	$obj->idate(10000081);
	$obj->save();
	
	fail();
}catch(\ebi\exception\InvalidArgumentException $e){
}


$obj = new \test\db\DateTime();
$obj->birthday(20170101);
$obj->save();

try{
	$obj = new \test\db\DateTime();
	$obj->birthday(120170101);
	$obj->save();
	
	fail();
}catch(ebi\Exceptions $e){
}

