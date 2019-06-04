<?php
$b = b();

$b->do_get(['index::automap_arg/mno','ABC','DEF']);
eq(200,$b->status());

eq('ABC',$b->json('result/A'));
eq('DEF',$b->json('result/B'));


