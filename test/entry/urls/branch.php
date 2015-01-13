<?php
$b = new \testman\Browser();
$b->do_get(url('urls::newapp#hoge'));
eq(200,$b->status());


$b->do_get(url('urls::app-def#hoge'));
eq(200,$b->status());


$b->do_get(url('urls::newapp#nosecure'));
eq(200,$b->status());
meq('http://',$b->url());

meq('https://',url('urls::newapp#secure'));


