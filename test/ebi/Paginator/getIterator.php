<?php
$p = new \ebi\Paginator(10,3);
$p->total(100);
$re = [];
foreach($p as $k => $v) $re[$k] = $v;
eq(array('current'=>3,'limit'=>10,'offset'=>20,'total'=>100,'order'=>null),$re);

