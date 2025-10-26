<?php
\test\db\UniqueCodeOne::create_table();
\test\db\UniqueCodeOne::find_delete();

try{
	$obj = new \test\db\UniqueCodeOne();
	$obj->save();
	
	
	$obj = new \test\db\UniqueCodeOne();
	$obj->save();
	
	$obj = new \test\db\UniqueCodeOne();
	$obj->save();
	
	fail();
}catch(\ebi\exception\GenerateUniqueCodeRetryLimitOverException $e){
	
}
