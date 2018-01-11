<?php
$b = b();
$b->do_post('login1::automap/aaa');

eq(401,$b->status());
eq(\testman\Util::url('login1::automap/do_login'),$b->url());

