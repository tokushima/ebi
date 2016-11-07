<?php
$b = b();
$b->vars('abc','aaa');
$b->do_get(url('index::group_aaa'));
meq('{"result":{"abc":"aaa"}}',$b->body());

$b->vars('abc','bbb');
$b->do_get(url('index::group_bbb'));
meq('{"result":{"abc":"bbb"}}',$b->body());

