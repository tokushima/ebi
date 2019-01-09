<?php
/**
 * 複数行インサート
 */
\test\db\Find::create_table();
\test\db\Find::find_delete();


$data_objects = [];
for($i=1;$i<=100;$i++){
	$obj = new \test\db\Find();
	$obj->value1($i);
	$obj->value2($i * -1);
	$obj->updated(time());
// 	$obj->save();
	
	$data_objects[] = $obj;
}
\test\db\Find::insert_multiple($data_objects);
eq(100,\test\db\Find::find_count());



$data_objects = [];
for($i=0;$i<10;$i++){
	$obj = new \test\db\Find();
	$obj->value1($i);
	
	$data_objects[] = $obj;
}
$fake = new \test\db\Abc();
$fake->value(-1);

$data_objects[] = $fake;

try{
	\test\db\Find::insert_multiple($data_objects);
	fail();
}catch(\ebi\exception\InvalidArgumentException $e){
}



