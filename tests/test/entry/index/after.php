<?php
$b = b();
$b->do_get('index::after');
eq(200,$b->status());
eq(\testman\Util::url('index::after_to'),$b->url());

$b->do_get('index::after_arg1');
eq(200,$b->status());
eq(\testman\Util::url(['index::after_to_arg1','ABC']),$b->url());

$b->do_get('index::after_arg2');
eq(200,$b->status());
eq(\testman\Util::url(['index::after_to_arg2','ABC','DEF']),$b->url());


$b->do_get('index::post_after');
eq(200,$b->status());
eq(\testman\Util::url('index::post_after'),$b->url());

$b->do_post('index::post_after');
eq(200,$b->status());
eq(\testman\Util::url('index::post_after_to'),$b->url());

$b->do_post('index::post_after_arg1');
eq(200,$b->status());
eq(\testman\Util::url(['index::post_after_to_arg1','ABC']),$b->url());

$b->do_post('index::post_after_arg2');
eq(200,$b->status());
eq(\testman\Util::url(['index::post_after_to_arg2','ABC','DEF']),$b->url());
