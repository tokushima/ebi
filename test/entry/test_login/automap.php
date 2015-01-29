<?php
$b = b();
$b->do_post(url('test_login::automap/aaa'));

eq(401,$b->status());
eq(url('test_login::automap/do_login'),$b->url());
