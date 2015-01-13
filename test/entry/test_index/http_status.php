<?php
$b = new \testman\Browser();

$b->do_get(url('test_index::status400'));
eq(400,$b->status());

$b->do_get(url('test_index::status403'));
eq(403,$b->status());

$b->do_get(url('test_index::status404'));
eq(404,$b->status());

$b->do_get(url('test_index::status405'));
eq(405,$b->status());

$b->do_get(url('test_index::status406'));
eq(406,$b->status());

$b->do_get(url('test_index::status409'));
eq(409,$b->status());

$b->do_get(url('test_index::status410'));
eq(410,$b->status());

$b->do_get(url('test_index::status415'));
eq(415,$b->status());

$b->do_get(url('test_index::status500'));
eq(500,$b->status());

$b->do_get(url('test_index::status503'));
eq(503,$b->status());


