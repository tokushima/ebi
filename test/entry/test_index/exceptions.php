<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::exceptions'));
eq(500,$b->status());
eq('{"error":[{"message":"raise test","type":"LogicException","group":"newgroup"}]}',$b->body());
