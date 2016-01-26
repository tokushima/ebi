<?php
use \ebi\Q;

\test\db\Crud::create_table();
\test\db\Crud::find_delete();

$start = microtime(true);
eq(0,\test\db\Crud::find_count());
for($i=1;$i<=10;$i++){
	(new \test\db\Crud())->value($i)->save();
}

eq(0,\test\db\Crud::find_count(Q::eq('value',-1)));

$time = microtime(true) - $start;
if($time > 1) notice($time);
eq(10,\test\db\Crud::find_count());

$start = microtime(true);
foreach(\test\db\Crud::find() as $o){
	$o->value($o->value()+1)->save();
}
$time = microtime(true) - $start;
if($time > 1) notice($time);
eq(10,\test\db\Crud::find_count());

$start = microtime(true);
foreach(\test\db\Crud::find() as $o){
	$o->delete();
}
$time = microtime(true) - $start;
if($time > 1) notice($time);
eq(0,\test\db\Crud::find_count());




