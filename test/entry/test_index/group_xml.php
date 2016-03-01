<?php
$b = b();
$b->vars('abc','aaa');
$b->do_get(url('test_index::group_aaa_xml'));
eq(200,$b->status());
meq('<result><abc>aaa</abc></result>',$b->body());

$b->vars('abc','bbb');
$b->do_get(url('test_index::group_bbb_xml'));
eq(200,$b->status());
meq('<result><abc>bbb</abc></result>',$b->body());


$b->vars('abc','bbb');
$b->do_get(url('test_index::group_eee_xml'));
eq(500,$b->status());
eq('<error><message><type>LogicException</type><value>raise test</value></message></error>',$b->body());


// json output
$b = b();
$b->header('Accept','application/json');
$b->vars('abc','aaa');
$b->do_get(url('test_index::group_aaa_xml'));
eq(200,$b->status());
meq('{"result":{"abc":"aaa"}}',$b->body());


$b->header('Accept','application/json');
$b->vars('abc','bbb');
$b->do_get(url('test_index::group_eee_xml'));
eq(500,$b->status());
eq('{"error":[{"message":"raise test","type":"LogicException"}]}',$b->body());

