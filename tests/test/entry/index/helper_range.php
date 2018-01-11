<?php
$b = b();
$b->do_get('index::helper_range');
eq(200,$b->status());
meq('A1234A',$b->body());
meq('B12345B',$b->body());
meq('C12345678C',$b->body());
