<?php
$b = new \testman\Browser();
$b->do_get(url('test_index::exceptions'));
eq(500,$b->status());
eq('{"error":[{"message":"invalid argument","type":"InvalidArgumentException"},{"message":"logic","type":"LogicException"}]}',$b->body());


$b->do_get(url('test_index::exceptions_group'));
eq(500,$b->status());
eq('{"error":[{"message":"invalid argument","type":"InvalidArgumentException","group":"newgroup"},{"message":"logic","type":"LogicException","group":"newgroup"}]}',$b->body());

