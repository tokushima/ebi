<?php
\test\db\DateTime::create_table();
\test\db\DateTime::find_delete();

$obj = new \test\db\DateTime();
$obj->idate(20170101);
$obj->save();


try{
	$obj = new \test\db\DateTime();
	$obj->idate(10000081);
	$obj->save();
	
	fail();
}catch(\ebi\exception\InvalidArgumentException $e){
}

