<?php
$b = b();
$b->do_get(url('index::require_post'));
eq(200,$b->status());
eq('BadMethodCallException',$b->json('error')[0]['type']);

$b->do_post(url('index::require_post'));
eq(200,$b->status());


$b->do_get(url('index::require_get'));
eq(200,$b->status());

$b->do_post(url('index::require_get'));
eq(200,$b->status());
eq('BadMethodCallException',$b->json('error')[0]['type']);



