<?php
namespace test\ebi\Dao;
\test\db\UniqueVerify::create_table();
\test\db\UniqueVerify::find_delete();


$obj = new \test\db\UniqueVerify();
$obj->u1(2);
$obj->u2(3);
$obj->save();


$obj = new \test\db\UniqueVerify();
$obj->u1(2);
$obj->u2(3);
try{
	$obj->save();
	fail();
}catch(\ebi\Exception $e){
}


$obj = new \test\db\UniqueVerify();
$obj->u1(2);
$obj->u2(4);
$obj->save();
