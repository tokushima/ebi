<?php
$ref = function($o){
	return $o;
};

\test\db\CrossParent::create_table();
\test\db\CrossParent::find_delete();
\test\db\CrossChild::create_table();
\test\db\CrossChild::find_delete();

$p1 = $ref(new \test\db\CrossParent())->value('A')->save();
$p2 = $ref(new \test\db\CrossParent())->value('B')->save();
$c1 = $ref(new \test\db\CrossChild())->parent_id($p1->id())->save();
$c2 = $ref(new \test\db\CrossChild())->parent_id($p2->id())->save();

$result = array($p1->id()=>'A',$p2->id()=>'B');
foreach(\test\db\CrossChild::find_all() as $o){
	eq(true,($o->parent() instanceof \test\db\CrossParent));
	eq($result[$o->parent()->id()],$o->parent()->value());
}
