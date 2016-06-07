<?php
\test\db\UniqueCodeDigit::create_table();
\test\db\UniqueCodeDigit::find_delete();


$obj = new \test\db\UniqueCodeDigit();
eq(null,$obj->code1());
eq(null,$obj->code2());
eq(null,$obj->code3());
$obj->save();

foreach(\test\db\UniqueCodeDigit::find() as $o){
	neq(null,$o->code1());
	neq(null,$o->code2());
	neq(null,$o->code3());
	eq(32,strlen($o->code1()));
	eq(10,strlen($o->code2()));
	eq(40,strlen($o->code3()));
	eq(true,ctype_digit($o->code1()));
	eq(true,ctype_digit($o->code2()));
	eq(true,ctype_digit($o->code3()));

	neq('000',substr($o->code2(),0,3));
	neq('000',substr($o->code2(),-3));
}
