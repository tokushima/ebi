<?php
$b = b();


$b->do_get('index::require_vars_invalid_anon');
eq(200,$b->status());

$error = <<< '_ERR'
{"error":[{"message":"annotation error : ` string $def @['require'=>true']`","type":"InvalidAnnotationException"}]}
_ERR;


eq($error,$b->body());



