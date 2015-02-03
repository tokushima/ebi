<?php
$b = b();
$b->do_get(url('urls::newapp#noresult'));

eq('<result />',$b->body());
