<?php
\test\db\DoublePrimary::create_table();
\test\db\DoublePrimary::find_delete();


try{
	$obj = new \test\db\DoublePrimary();
	$obj->id1(1)->id2(1)->value("hoge")->save();
}catch(\ebi\Exception $e){
	fail();
}
$p = new \test\db\DoublePrimary();
eq("hoge",$p->id1(1)->id2(1)->sync()->value());
