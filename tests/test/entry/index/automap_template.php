<?php
$b = b();

$b->do_get(url('index::ABC/def'));
meq('DEF',$b->body());

