<?php
$b = b();

$b->vars('value',__FILE__.'A');
$b->do_post(url('test_index::abc'));
$pre_id = $b->json('result/id');

$b->vars('value',__FILE__.'B');
$b->do_post(url('test_index::abc'));
eq($pre_id+1,$b->json('result/id'));


// GET
$b->vars('value',__FILE__.'C');
$b->do_get(url('test_index::abc'));
eq('BadMethodCallException',$b->json('error')[0]['type']);


