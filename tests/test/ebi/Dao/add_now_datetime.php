<?php
\test\db\DateTime::create_table();
\test\db\AddNowDateTime::find_delete();

$obj = new \test\db\AddNowDateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(\test\db\AddNowDateTime::find() as $o){
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
}