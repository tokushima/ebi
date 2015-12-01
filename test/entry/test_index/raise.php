<?php
$b = b();
$b->do_get(url('test_index::raise'));
eq(500,$b->status());
eq('{"error":[{"message":"raise test","type":"LogicException"}]}',$b->body());
