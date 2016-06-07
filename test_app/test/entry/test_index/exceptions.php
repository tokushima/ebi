<?php
$b = b();
$b->do_get(url('test_index::exceptions'));
eq(200,$b->status());
eq('{"error":[{"message":"invalid argument","type":"InvalidArgumentException"},{"message":"logic","type":"LogicException"}]}',$b->body());


$b->do_get(url('test_index::exceptions403'));
eq(403,$b->status());
eq('{"error":[{"message":"invalid argument","type":"InvalidArgumentException"},{"message":"logic","type":"LogicException"}]}',$b->body());

$b->do_get(url('test_index::exceptions405'));
eq(405,$b->status());
eq('{"error":[{"message":"Method Not Allowed","type":"LogicException"}]}',$b->body());

$b->do_get(url('test_index::exceptions_group'));
eq(200,$b->status());
eq('{"error":[{"message":"invalid argument","type":"InvalidArgumentException","group":"newgroup"},{"message":"logic","type":"LogicException","group":"newgroup"}]}',$b->body());

