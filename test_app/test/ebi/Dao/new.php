<?php
use \ebi\Q;
\test\db\NewDao::create_table();
\test\db\NewDao::find_delete();


$obj = new \test\db\NewDao();
$obj->value('aaa');
$obj->save();

$obj = new \test\db\NewDao();
$obj->value('bbb');
$obj->save();


foreach(\test\db\NewDao::find() as $o){
	neq(null,$o->value());
}
foreach(\test\db\NewDao::find(\ebi\Q::eq('value','aaa')) as $o){
	eq('aaa',$o->value());
}


$obj = new \test\db\NewDao();
neq(null,$obj);


\test\db\NewDao::find_delete();
eq(0,\test\db\NewDao::find_count());

$obj = new \test\db\NewDao();
$obj->save();

$obj = new \test\db\NewDao();
$obj->value(null);
$obj->save();

$obj = new \test\db\NewDao();
$obj->value('');
$obj->save();

eq(1,\test\db\NewDao::find_count(Q::eq('value','')));
eq(2,\test\db\NewDao::find_count(Q::eq('value',null)));
eq(3,\test\db\NewDao::find_count());

$r = array(null,null,'');
$i = 0;
foreach(\test\db\NewDao::find(Q::order('id')) as $o){
	eq($r[$i],$o->value());
	$i++;
}
