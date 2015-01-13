<?php
\test\db\UniqueTripleVerify::create_table();
\test\db\UniqueTripleVerify::find_delete();


$obj = new \test\db\UniqueTripleVerify();
$obj->u1(2);
$obj->u2(3);
$obj->u3(4);
$obj->save();


$obj = new \test\db\UniqueTripleVerify();
$obj->u1(2);
$obj->u2(3);
$obj->u3(4);
try{
	$obj->save();
	fail();
}catch(\ebi\Exception $e){
}

$obj = new \test\db\UniqueTripleVerify();
$obj->u1(2);
$obj->u2(4);
$obj->u3(4);
$obj->save();

