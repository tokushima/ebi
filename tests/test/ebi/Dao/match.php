<?php
use \ebi\Q;

\test\db\MatchData::create_table();
\test\db\MatchData::find_delete();

(new \test\db\MatchData())->data1(10)->data2('XXX')->data3('AAABBB')->save();
(new \test\db\MatchData())->data1(20)->data2('YYY')->data3('BBBCCC')->save();
(new \test\db\MatchData())->data1(30)->data2('BBB')->data3('CCCDDD')->save();
(new \test\db\MatchData())->data1(40)->data2('IIIAAABBBEEE')->data3('AAADDDBBB')->save();
(new \test\db\MatchData())->data1(50)->data2('JJJ')->data3('EEEFFFIII')->save();

eq(4,\test\db\MatchData::find_count(Q::match('BBB')));
eq(2,\test\db\MatchData::find_count(Q::match('AAA BBB')));
eq(0,\test\db\MatchData::find_count(Q::match('BB YY')));
eq(1,\test\db\MatchData::find_count(Q::match('BBB III')));
eq(2,\test\db\MatchData::find_count(Q::match('EE II')));
eq(1,\test\db\MatchData::find_count(Q::match(30)));
eq(1,\test\db\MatchData::find_count(Q::match('BB　CC'))); // 全角スペース

eq(1,\test\db\MatchData::find_count(Q::match('BBB CCC',['data3'])));


$result = [10,20,30,40];
$i = 0;
foreach(\test\db\MatchData::find(Q::match('BBB',['data3','data2']),Q::order('data1')) as $o){
	eq($result[$i],$o->data1());
	$i++;
}


$result = [10,40];
$i = 0;
foreach(\test\db\MatchData::find(Q::match('AAA BBB',['data3','data2']),Q::order('data1')) as $o){
	eq($result[$i],$o->data1());
	$i++;
}

$result = [40];
$i = 0;
foreach(\test\db\MatchData::find(Q::match('AAA BBB',['data2']),Q::order('data1')) as $o){
	eq($result[$i],$o->data1());
	$i++;
}
