<?php
$b = b();

$b->vars('value',__FILE__.'A');
$b->do_post('index::abc');
$pre_id = $b->json('result/id');

$b->vars('value',__FILE__.'B');
$b->do_post('index::abc');
eq($pre_id+1,$b->json('result/id'));


