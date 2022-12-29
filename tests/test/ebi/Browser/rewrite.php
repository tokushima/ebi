<?php

$b = new \ebi\Browser();
$b->do_get(\testman\Util::url('index::rewrite/abc'));
eq('DEF', $b->json('result/value'));
