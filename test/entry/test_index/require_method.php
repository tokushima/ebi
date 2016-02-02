<?php
$b = b();
$b->do_get(url('test_index::require_post'));
eq(500,$b->status());

$b->do_post(url('test_index::require_post'));
eq(200,$b->status());


$b->do_get(url('test_index::require_get'));
eq(200,$b->status());

$b->do_post(url('test_index::require_get'));
eq(500,$b->status());




