<?php
\test\db\DateTime::create_table();
\test\db\DateTime::find_delete();

$obj = new \test\db\DateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(\test\db\DateTime::find() as $o){
	eq(null,$o->ts());
	eq(null,$o->date());
	eq(null,$o->idate());
}