<?php
/**
 * deprecatedでもアクセスできる
 */
$b = b();

$b->do_get(url('index::deprecated'));
eq(200,$b->status());

$b->do_get(url('index::deprecated_method'));
eq(200,$b->status());


