<?php
$b = b();

$b->do_get('index::parts_parts1');
meq('AAAAAAAAAAAAA',$b->body());
meq('BBBBBBBBBBBBB',$b->body());
meq('CCCCCCCCCCCCC',$b->body());
