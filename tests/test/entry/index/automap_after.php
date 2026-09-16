<?php
$b = b();

$b->do_get('index::ABC/after');
eq(\testman\Util::url('index::ABC/after_a'), $b->url());


$b->do_post('index::ABC/after');
eq(\testman\Util::url('index::ABC/after_b'), $b->url());

