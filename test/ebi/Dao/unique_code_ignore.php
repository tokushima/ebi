<?php
\test\db\UniqueCodeIgnore::create_table();
\test\db\UniqueCodeIgnore::find_delete();

$i=0;
while(true){
	try{
		$obj = new \test\db\UniqueCodeIgnore();
		eq(null,$obj->code1());
		$obj->save();
		
		break;
	}catch(\ebi\exception\GenerateUniqueCodeRetryLimitOverException $e){
		if($i++ > 1000){
			throw $e;
		}
	}
}


foreach(\test\db\UniqueCodeIgnore::find() as $o){
	neq(null,$o->code1());
	eq(1,strlen($o->code1()));
	eq(true,ctype_digit($o->code1()));
	eq('9',$o->code1());
}
