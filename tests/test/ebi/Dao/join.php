<?php
use \ebi\Q;

\test\db\JoinA::create_table();
\test\db\JoinA::find_delete();

\test\db\JoinB::create_table();
\test\db\JoinB::find_delete();

\test\db\JoinC::create_table();
\test\db\JoinC::find_delete();

$a1 = (new \test\db\JoinA())->save();
$a2 = (new \test\db\JoinA())->save();
$a3 = (new \test\db\JoinA())->save();
$a4 = (new \test\db\JoinA())->save();
$a5 = (new \test\db\JoinA())->save();
$a6 = (new \test\db\JoinA())->save();

$b1 = (new \test\db\JoinB())->name("aaa")->save();
$b2 = (new \test\db\JoinB())->name("bbb")->save();

$c1 = (new \test\db\JoinC())->a_id($a1->id())->b_id($b1->id())->save();
$c2 = (new \test\db\JoinC())->a_id($a2->id())->b_id($b1->id())->save();
$c3 = (new \test\db\JoinC())->a_id($a3->id())->b_id($b1->id())->save();
$c4 = (new \test\db\JoinC())->a_id($a4->id())->b_id($b2->id())->save();
$c5 = (new \test\db\JoinC())->a_id($a4->id())->b_id($b1->id())->save();
$c6 = (new \test\db\JoinC())->a_id($a5->id())->b_id($b2->id())->save();
$c7 = (new \test\db\JoinC())->a_id($a5->id())->b_id($b1->id())->save();

$re = \test\db\JoinABC::find_all();
eq(7,sizeof($re));

$re = \test\db\JoinABC::find_all(Q::eq("name","aaa"));
eq(5,sizeof($re));

$re = \test\db\JoinABC::find_all(Q::eq("name","bbb"));
eq(2,sizeof($re));


$re = \test\db\JoinABBCC::find_all(Q::eq("name","bbb"));
eq(2,sizeof($re));




