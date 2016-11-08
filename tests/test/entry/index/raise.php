<?php
$b = b();
$b->do_get(url('index::raise'));
eq(200,$b->status());
eq('{"error":[{"message":"raise test","type":"LogicException"}]}',$b->body());
