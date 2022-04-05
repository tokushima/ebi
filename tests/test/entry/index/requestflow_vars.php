<?php
$b = b();

$b->do_get('index::requestflow_vars');
eq('{"result":{"abc":123,"def":456}}',$b->body());


$b->do_get('index::requestflow_vars_callback');
eq('{"result":{"abc":123,"def":456}}',$b->body());


$b->vars('callback','hoge');
$b->do_get('index::requestflow_vars_callback');
eq('{"result":{"abc":123,"def":456,"callback":"hoge"}}',$b->body());




