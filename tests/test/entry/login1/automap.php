<?php
$b = b();
$b->do_post(url('login1::automap/aaa'));

eq(401,$b->status());
eq(url('login1::automap/do_login'),$b->url());

