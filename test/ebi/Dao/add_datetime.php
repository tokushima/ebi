<?php
\test\db\AddDateTime::create_table();
\test\db\AddDateTime::find_delete();

$obj = new \test\db\AddDateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(\test\db\AddDateTime::find() as $o){
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
}



\test\db\AutoNow::create_table();
\test\db\AutoNow::find_delete();

$obj = new \test\db\AutoNow();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

$b = null;
foreach(\test\db\AutoNow::find() as $o){
	$b = $o->ts();
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
	
	sleep(1);
	$o->save();
}
foreach(\test\db\AutoNow::find() as $o){
	neq($b,$o->ts());
}


