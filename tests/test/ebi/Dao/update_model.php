<?php
use \ebi\Q;

\test\db\UpdateModel::create_table();
\test\db\UpdateModel::find_delete();

(new \test\db\UpdateModel())->value('abc')->save();
(new \test\db\UpdateModel())->value('def')->save();
(new \test\db\UpdateModel())->value('def')->save();
(new \test\db\UpdateModel())->value('def')->save();
(new \test\db\UpdateModel())->value('ghi')->save();

eq(5,\test\db\UpdateModel::find_count());
\test\db\UpdateModel::find_delete(Q::eq('value','def'));
eq(2,\test\db\UpdateModel::find_count());


\test\db\UpdateModel::find_delete();
$d1 = (new \test\db\UpdateModel())->value('abc')->save();
$d2 = (new \test\db\UpdateModel())->value('def')->save();
$d3 = (new \test\db\UpdateModel())->value('ghi')->save();

eq(3,\test\db\UpdateModel::find_count());
$obj = new \test\db\UpdateModel();
$obj->id($d1->id())->delete();
eq(2,\test\db\UpdateModel::find_count());
$obj = new \test\db\UpdateModel();
$obj->id($d3->id())->delete();
eq(1,\test\db\UpdateModel::find_count());
eq('def',\test\db\UpdateModel::find_get()->value());


\test\db\UpdateModel::find_delete();
$s1 = (new \test\db\UpdateModel())->value('abc')->save();
$s2 = (new \test\db\UpdateModel())->value('def')->save();
$s3 = (new \test\db\UpdateModel())->value('ghi')->save();

eq(3,\test\db\UpdateModel::find_count());


\test\db\UpdateModel::find_delete();
$s1 = (new \test\db\UpdateModel())->value('abc')->save();
$s2 = (new \test\db\UpdateModel())->value('def')->save();

\test\db\UpdateModel::find_delete();



// ----------

$s1 = new \test\db\UpdateModel();
$s1->value('AAA');
$s1->abc('BBB');
$s1->def('CCC');
$s1->ghi('DDD');
$s1->save();

eq('AAA',$s1->value());
eq('BBB',$s1->abc());
eq('CCC',$s1->def());
eq('DDD',$s1->ghi());

$s1->value('111');
$s1->abc('222');
$s1->def('333');
$s1->ghi('444');
$s1->save();

$a1 = \test\db\UpdateModel::find_get(Q::eq('id',$s1->id()));
eq('111',$a1->value());
eq('222',$a1->abc());
eq('333',$a1->def());
eq('444',$a1->ghi());



// ----------

$s1 = new \test\db\UpdateModel();
$s1->value('AAA');
$s1->abc('BBB');
$s1->def('CCC');
$s1->ghi('DDD');
$s1->save();

eq('AAA',$s1->value());
eq('BBB',$s1->abc());
eq('CCC',$s1->def());
eq('DDD',$s1->ghi());

$s1->value('111');
$s1->abc('222');
$s1->def('333');
$s1->ghi('444');
$s1->save('abc','def');

$a1 = \test\db\UpdateModel::find_get(Q::eq('id',$s1->id()));
eq('AAA',$a1->value());
eq('222',$a1->abc());
eq('333',$a1->def());
eq('DDD',$a1->ghi());


// ----------

$s1 = new \test\db\UpdateModel();
$s1->value('AAA');
$s1->abc('BBB');
$s1->def('CCC');
$s1->ghi('DDD');
$s1->save();

eq('AAA',$s1->value());
eq('BBB',$s1->abc());
eq('CCC',$s1->def());
eq('DDD',$s1->ghi());

$s1->value('111');
$s1->abc('222');
$s1->def('333');
$s1->ghi('444');

try{
	$s1->save('abc',' xyz');
	eq(false,true);
}catch(\ebi\exception\InvalidQueryException $e){
	eq(true,true);
}



