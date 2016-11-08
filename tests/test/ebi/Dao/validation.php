<?php
use \ebi\Q;
\test\db\Validation::create_table();
\test\db\Validation::find_delete();

try{
	$o = new \test\db\Validation();
	$o->value('abc');
	$o->save();
	
	fail();
}catch(\ebi\Exceptions $e){
	foreach($e as $g => $v){
		eq('value',$g);
	}
}

