<?php
\test\db\ManyParent::create_table();
\test\db\ManyParent::find_delete();

\test\db\ManyChild::create_table();
\test\db\ManyChild::find_delete();


$p1 = (new \test\db\ManyParent())->value('parent1')->save();
$p2 = (new \test\db\ManyParent())->value('parent2')->save();

$c1 = (new \test\db\ManyChild())->parent_id($p1->id())->value('child1-1')->save();
$c2 = (new \test\db\ManyChild())->parent_id($p1->id())->value('child1-2')->save();
$c3 = (new \test\db\ManyChild())->parent_id($p1->id())->value('child1-3')->save();
$c4 = (new \test\db\ManyChild())->parent_id($p2->id())->value('child2-1')->save();
$c5 = (new \test\db\ManyChild())->parent_id($p2->id())->value('child2-2')->save();

$size = array(3,2);
$i = 0;
foreach(\test\db\ManyParent::find() as $r){
	eq($size[$i],sizeof($r->children()));
	$i++;
}
$i = 0;
foreach(\test\db\ManyParent::find_all() as $r){
	eq($size[$i],sizeof($r->children()));
	foreach($r->children() as $child){
		eq(true,($child instanceof \test\db\ManyChild));
		eq($r->id(),$child->parent_id());
	}
	$i++;
}
