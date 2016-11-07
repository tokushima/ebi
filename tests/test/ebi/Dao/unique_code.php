<?php
\test\db\UniqueCode::create_table();
\test\db\UniqueCode::find_delete();


$obj = new \test\db\UniqueCode();
eq(null,$obj->code1());
eq(null,$obj->code2());
eq(null,$obj->code3());
$obj->save();

foreach(\test\db\UniqueCode::find() as $o){
	neq(null,$o->code1());
	neq(null,$o->code2());
	neq(null,$o->code3());
	eq(32,strlen($o->code1()));
	eq(10,strlen($o->code2()));
	eq(40,strlen($o->code3()));
}
