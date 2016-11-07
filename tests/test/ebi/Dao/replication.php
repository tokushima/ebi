<?php
\test\db\Replication::create_table();
\test\db\Replication::find_delete();


$result = \test\db\ReplicationSlave::find_all();
eq(0,sizeof($result));

try{
	$obj = new \test\db\ReplicationSlave();
	$obj->value('hoge')->save();
	fail();
}catch(\ebi\exception\BadMethodCallException $e){
}

$result = \test\db\ReplicationSlave::find_all();
eq(0,sizeof($result));

try{
	$obj = new \test\db\Replication();
	$obj->value('hoge');
	$obj->save();
}catch(\ebi\exception\BadMethodCallException $e){
	fail();
}

$result = \test\db\ReplicationSlave::find_all();
eq(1,sizeof($result));

$result = \test\db\Replication::find_all();
if(eq(1,sizeof($result))){
	eq('hoge',$result[0]->value());

	try{
		$result[0]->value('fuga');
		$result[0]->save();
		eq('fuga',$result[0]->value());
	}catch(\ebi\exception\BadMethodCallException $e){
		fail();
	}
}

