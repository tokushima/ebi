<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::template_parent'));
eq(200,$b->status());
meq('Parent',$b->body());
meq('default_child',$b->body());

$b->do_get(url('test_index::template_child'));
eq(200,$b->status());
meq('Parent',$b->body());
mneq('default_child',$b->body());
meq('new_child',$b->body());
meq('default_grandchild',$b->body());

$b->do_get(url('test_index::template_grandchild'));
eq(200,$b->status());
meq('Parent',$b->body());
mneq('default_child',$b->body());
meq('new_child',$b->body());
mneq('default_grandchild',$b->body());
meq('new_grandchild',$b->body());

$b->do_get(url('test_index::template_grandchild_super'));
eq(200,$b->status());
mneq('Parent',$b->body());
meq('Super',$b->body());
mneq('default_super_child',$b->body());
meq('new_child',$b->body());
mneq('default_grandchild',$b->body());
meq('new_grandchild',$b->body());

