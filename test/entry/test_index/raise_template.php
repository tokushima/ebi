<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::raise_template'));
eq(500,$b->status());
meq('raise test',$b->body());

