<?php
use ebi\Q;

$obj = new \test\db\Boolean();
$obj->save();

$find = \test\db\Boolean::find_get(Q::eq('id',$obj->id()));
eq(null,$find->value());


$obj = new \test\db\Boolean();
$obj->value(true);
$obj->save();

$find = \test\db\Boolean::find_get(Q::eq('id',$obj->id()));
eq(true,$find->value());


$obj = new \test\db\Boolean();
$obj->value(false);
$obj->save();

$find = \test\db\Boolean::find_get(Q::eq('id',$obj->id()));
eq(false,$find->value());




