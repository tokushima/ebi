<?php
$b = b();
$b->do_get('index::require_post');
eq(200,$b->status());
eq('BadMethodCallException',$b->json('error')[0]['type']);

$b->do_post('index::require_post');
eq(200,$b->status());


$b->do_get('index::require_get');
eq(200,$b->status());

$b->do_post('index::require_get');
eq(200,$b->status());
eq('BadMethodCallException',$b->json('error')[0]['type']);



