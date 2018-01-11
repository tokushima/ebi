<?php
$b = b();

$b->do_get('index::ABC/def');
meq('DEF',$b->body());

