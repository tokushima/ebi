<?php
$b = b();
$b->vars('abc','aaa');
$b->do_get(url('test_index::require_vars'));

eq(500,$b->status());
eq('{"error":[{"message":"def required","type":"RequiredException","group":"def"}]}',$b->body());


$b->vars('abc','aaa');
$b->vars('def','aaa');
$b->do_get(url('test_index::require_vars'));

eq(200,$b->status());



