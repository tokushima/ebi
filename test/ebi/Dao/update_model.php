<?php
use \ebi\Q;

$ref = function($o){
	return $o;
};
\test\db\UpdateModel::create_table();
\test\db\UpdateModel::find_delete();

$ref(new \test\db\UpdateModel())->value('abc')->save();
$ref(new \test\db\UpdateModel())->value('def')->save();
$ref(new \test\db\UpdateModel())->value('def')->save();
$ref(new \test\db\UpdateModel())->value('def')->save();
$ref(new \test\db\UpdateModel())->value('ghi')->save();

eq(5,\test\db\UpdateModel::find_count());
\test\db\UpdateModel::find_delete(Q::eq('value','def'));
eq(2,\test\db\UpdateModel::find_count());


\test\db\UpdateModel::find_delete();
$d1 = $ref(new \test\db\UpdateModel())->value('abc')->save();
$d2 = $ref(new \test\db\UpdateModel())->value('def')->save();
$d3 = $ref(new \test\db\UpdateModel())->value('ghi')->save();

eq(3,\test\db\UpdateModel::find_count());
$obj = new \test\db\UpdateModel();
$obj->id($d1->id())->delete();
eq(2,\test\db\UpdateModel::find_count());
$obj = new \test\db\UpdateModel();
$obj->id($d3->id())->delete();
eq(1,\test\db\UpdateModel::find_count());
eq('def',\test\db\UpdateModel::find_get()->value());


\test\db\UpdateModel::find_delete();
$s1 = $ref(new \test\db\UpdateModel())->value('abc')->save();
$s2 = $ref(new \test\db\UpdateModel())->value('def')->save();
$s3 = $ref(new \test\db\UpdateModel())->value('ghi')->save();

eq(3,\test\db\UpdateModel::find_count());
$obj = new \test\db\UpdateModel();
$obj->id($s1->id())->sync();
eq('abc',$obj->value());

$obj->value('hoge');
$obj->save();
$obj = new \test\db\UpdateModel();
$obj->id($s1->id())->sync();
eq('hoge',$obj->value());


\test\db\UpdateModel::find_delete();
$s1 = $ref(new \test\db\UpdateModel())->value('abc')->save();
$s2 = $ref(new \test\db\UpdateModel())->value('def')->save();

eq(2,\test\db\UpdateModel::find_count());
$obj = new \test\db\UpdateModel();
$obj->id($s1->id())->sync();
eq('abc',$obj->value());
$obj = new \test\db\UpdateModel();
$obj->id($s2->id())->sync();
eq('def',$obj->value());

$obj = new \test\db\UpdateModel();
try{
	$obj->id($s2->id()+100)->sync();
	fail();
}catch(\ebi\exception\NotFoundException $e){
}
\test\db\UpdateModel::find_delete();