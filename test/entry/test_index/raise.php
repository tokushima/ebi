<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::raise'));
eq(500,$b->status());
eq('{"error":[{"message":"raise test","group":"","type":"LogicException"}]}',$b->body());
