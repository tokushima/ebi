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



$b->vars('email','abc@def.ghi');
$b->do_get(url('test_index::require_type_email'));
eq(200,$b->status());


$b->vars('email','abc');
$b->do_get(url('test_index::require_type_email'));
eq(500,$b->status());
eq('{"error":[{"message":"email must be an email","type":"InvalidArgumentException","group":"email"}]}',$b->body());



