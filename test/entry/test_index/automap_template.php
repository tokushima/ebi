<?php
$b = b();

$b->do_get(url('test_index::ABC/def'));
meq('DEF',$b->body());

