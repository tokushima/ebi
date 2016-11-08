<?php
$b = b();

$b->do_get(url('index::requestflow_vars'));
eq('{"result":{"abc":123,"def":456}}',$b->body());


$b->do_get(url('index::requestflow_vars_callback'));
eq('{"result":{"abc":123,"def":456}}',$b->body());


$b->vars('callback','hoge');
$b->do_get(url('index::requestflow_vars_callback'));
eq('{"result":{"abc":123,"def":456,"callback":"hoge","result_data":"XYZ"}}',$b->body());



$b->do_get(url('index::requestflow_vars_callback_addvars'));
eq('{"result":{"abc":123,"def":456,"add1":"AAA","add2":"BBB"}}',$b->body());


$b->vars('callback','hoge');
$b->do_get(url('index::requestflow_vars_callback_addvars'));
eq('{"result":{"abc":123,"def":456,"callback":"hoge","result_data":"XYZ","add1":"AAA","add2":"BBB"}}',$b->body());





