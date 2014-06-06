<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_index::exceptions'));
eq(500,$b->status());
eq('{"error":[{"message":"raise test","group":"newgroup","type":"LogicException"}]}',$b->body());
