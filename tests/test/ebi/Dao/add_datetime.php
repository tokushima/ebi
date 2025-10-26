<?php
use \ebi\Q;

\test\db\AddDateTime::create_table();
\test\db\AddDateTime::find_delete();

$obj = new \test\db\AddDateTime();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

foreach(\test\db\AddDateTime::find() as $o){
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
}



\test\db\AutoNow::create_table();
\test\db\AutoNow::find_delete();

$obj = new \test\db\AutoNow();
eq(null,$obj->ts());
eq(null,$obj->date());
eq(null,$obj->idate());
$obj->save();

$b = null;
foreach(\test\db\AutoNow::find() as $o){
	$b = $o->ts();
	neq(null,$o->ts());
	neq(null,$o->date());
	neq(null,$o->idate());
	
	sleep(1);
	$o->save();
}
foreach(\test\db\AutoNow::find() as $o){
	neq($b,$o->ts());
}


$model1 = \test\db\AutoNow::find_get();
$model1->ts(time() - 86400);
$model1->date(time() - 86400);
$model1->idate(date('Ymd',time() - 86400));
$model1->value1('abc');
$model1->value2('def');
$model1->save('value1'); // value1だけ更新する

$model2 = \test\db\AutoNow::find_get(Q::eq('id',$model1->id()));
eq(true,($model2->ts() > time() - 1)); // 指定しない現在時間で更新されている
eq(date('Ymd'),($model2->fm_date('Ymd'))); // 指定しないが現在時間で更新されている
eq(date('Ymd'),$model2->idate()); // 指定しないが現在時間で更新されている
eq('abc',$model2->value1());
neq('def',$model2->value2()); // 更新されてない






