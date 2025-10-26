<?php
/**
 * deprecatedでもアクセスできる
 */
$b = b();

$b->do_get('index::deprecated');
eq(200,$b->status());

$b->do_get('index::deprecated_method');
eq(200,$b->status());


