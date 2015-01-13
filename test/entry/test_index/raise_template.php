<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::raise_template'));
eq(200,$b->status());
meq('raise test',$b->body());

