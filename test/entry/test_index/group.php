<?php
$b = new \testman\Browser();
$b->vars('abc','aaa');
$b->do_get(test_map_url('test_index::group_aaa'));
eq(200,$b->status());
meq('{"result":{"abc":"aaa"}}',$b->body());

$b->vars('abc','bbb');
$b->do_get(test_map_url('test_index::group_bbb'));
eq(200,$b->status());
meq('{"result":{"abc":"bbb"}}',$b->body());

