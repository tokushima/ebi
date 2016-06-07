<?php

$b = b();

$b->do_get(url('test_index::requestflow_vars_template'));
eq(200,$b->status());
meq('<html>',$b->body());
meq('AAA123456BBB',$b->body());


$b->header('accept','application/debug');
$b->do_get(url('test_index::requestflow_vars_template'));
eq(200,$b->status());
eq('{"result":{"abc":123,"def":456}}',$b->body());


