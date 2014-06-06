<?php
use \ebi\Q;

$ref = function($o){
	return $o;
};

\test\db\Crud::create_table();
\test\db\Crud::find_delete();

$start = microtime(true);
eq(0,\test\db\Crud::find_count());
for($i=1;$i<=10;$i++){
	$ref(new \test\db\Crud())->value($i)->save();
}

eq(0,\test\db\Crud::find_count(Q::eq('value',-1)));
$it = \test\db\Crud::find(Q::eq('value',-1));
$it->rewind();
eq(false,$it->valid());


$it = \test\db\Crud::find();
eq(true,$it->valid());
$it2 = clone($it);
$i = 0;
foreach($it as $o){
	$i++;
	eq($i,$o->value());
}
eq(10,$i);

foreach($it as $o){
	$i--;
}
eq(10,$i);

foreach($it2 as $o){
	$i--;
}
eq(10,$i);

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




