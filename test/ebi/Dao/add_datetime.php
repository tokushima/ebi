<?php
\test\db\AddDateTime::create_table();
\test\db\AddDateTime::find_delete();

$obj = new \test\db\AddDateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(\test\db\AddNowDateTime::find() as $o){
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
}
