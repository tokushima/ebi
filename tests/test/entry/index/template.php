<?php
$b = b();
$b->do_get('index::template_parent');
eq(200,$b->status());
meq('Parent',$b->body());
meq('default_child',$b->body());

$b->do_get('index::template_child');
eq(200,$b->status());
meq('Parent',$b->body());
mneq('default_child',$b->body());
meq('new_child',$b->body());
meq('default_grandchild',$b->body());

$b->do_get('index::template_grandchild');
eq(200,$b->status());
meq('Parent',$b->body());
mneq('default_child',$b->body());
meq('new_child',$b->body());
mneq('default_grandchild',$b->body());
meq('new_grandchild',$b->body());

