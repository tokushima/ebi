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


// json output
$b = b();
$b->header('response_content_type','application/json');
$b->vars('abc','aaa');
$b->do_get(url('test_index::group_aaa_xml'));
eq(200,$b->status());
meq('{"result":{"abc":"aaa"}}',$b->body());

