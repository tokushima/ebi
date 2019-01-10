<?php
use \ebi\Q;
/**
 * 複数行UPDATE
 */
\test\db\Find::create_table();
\test\db\Find::find_delete();


$data_objects = [];
for($i=1;$i<=100;$i++){
	$obj = new \test\db\Find();
	$obj->value1('A'.$i);
	$obj->value2('B'.($i * -1));
	$obj->order($i);
	$obj->updated(time());
	$data_objects[] = $obj;
}
\test\db\Find::insert_multiple($data_objects);
eq(100,\test\db\Find::find_count());

eq(50,\test\db\Find::find_count(Q::lte('order',50)));


$dao = new \test\db\Find();
$dao->value1('AAAA');
$dao->value2('BBBB');
$dao->updated(strtotime('2019-01-01 12:13:14'));

$cnt = \test\db\Find::find_update($dao,Q::lte('order',50),'value1','updated');
eq(50,$cnt);

foreach(\test\db\Find::find(Q::lte('order',50)) as $obj){
	eq('AAAA',$obj->value1());
	neq('BBBB',$obj->value2());
	eq('2019-01-01 12:13:14',$obj->fm_updated('Y-m-d H:i:s'));
}


$dao = new \test\db\Find();
$dao->value2('AAAA');

try{
	\test\db\Find::find_update($dao,Q::lte('id',50));
	fail();
}catch(\ebi\exception\InvalidArgumentException $e){
}