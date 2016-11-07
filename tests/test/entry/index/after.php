<?php
$b = b();
$b->do_get(url('index::after'));
eq(200,$b->status());
eq(url('index::after_to'),$b->url());

$b->do_get(url('index::after_arg1'));
eq(200,$b->status());
eq(url('index::after_to_arg1','ABC'),$b->url());

$b->do_get(url('index::after_arg2'));
eq(200,$b->status());
eq(url('index::after_to_arg2','ABC','DEF'),$b->url());


$b->do_get(url('index::post_after'));
eq(200,$b->status());
eq(url('index::post_after'),$b->url());

$b->do_post(url('index::post_after'));
eq(200,$b->status());
eq(url('index::post_after_to'),$b->url());

$b->do_post(url('index::post_after_arg1'));
eq(200,$b->status());
eq(url('index::post_after_to_arg1','ABC'),$b->url());

$b->do_post(url('index::post_after_arg2'));
eq(200,$b->status());
eq(url('index::post_after_to_arg2','ABC','DEF'),$b->url());
