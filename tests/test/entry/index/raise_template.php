<?php
$b = b();
$b->do_get(url('index::raise_template'));
eq(200,$b->status());
meq('Error: raise test',$b->body());
mneq('rt:invalid',$b->body());



$b->do_get(url('index::raise_template_parent'));
eq(200,$b->status());
meq('Error: raise test',$b->body());
mneq('rt:invalid',$b->body());

