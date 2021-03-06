<?php
$b = b();
$b->vars('abc','aaa');
$b->do_get('index::group_aaa_xml');
eq(200,$b->status());
meq('<result><abc>aaa</abc></result>',$b->body());

$b->vars('abc','bbb');
$b->do_get('index::group_bbb_xml');
eq(200,$b->status());
meq('<result><abc>bbb</abc></result>',$b->body());


$b->vars('abc','bbb');
$b->do_get('index::group_eee_xml');
eq(200,$b->status());
eq('<error><message type="LogicException">raise test</message></error>',$b->body());


// json output
$b = b();
$b->header('Accept','application/json');
$b->vars('abc','aaa');
$b->do_get('index::group_aaa_xml');
eq(200,$b->status());
meq('{"result":{"abc":"aaa"}}',$b->body());


$b->header('Accept','application/json');
$b->vars('abc','bbb');
$b->do_get('index::group_eee_xml');
eq(200,$b->status());
eq('{"error":[{"message":"raise test","type":"LogicException"}]}',$b->body());

