<?php
use \ebi\Q;

\test\db\DateFormat::create_table();
\test\db\DateFormat::find_delete();

$date = strtotime('2015/07/04 00:00:00'); // 2015/07/04T00:00:00 +09:00
$obj = new \test\db\DateFormat();
$obj->ts($date);
$obj->num(10);
$obj->save();

foreach(\test\db\DateFormat::find() as $o){
	eq(date('c',$date),$o->fm_ts());
}

foreach(\test\db\DateFormat::find(Q::date_format('ts','Ym')) as $o){
	eq(date('c',strtotime('2015/07/01 00:00:00')),$o->fm_ts());
}

$date = strtotime('2015/07/01 12:34:56');
$obj = new \test\db\DateFormat();
$obj->ts($date);
$obj->num(20);
$obj->save();

$date = strtotime('2015/07/30 12:34:56');
$obj = new \test\db\DateFormat();
$obj->ts($date);
$obj->num(30);
$obj->save();

foreach(\test\db\DateFormat::find(Q::date_format('ts','Ym')) as $o){
	eq(date('c',strtotime('2015/07/01 00:00:00')),$o->fm_ts());
}

$date = strtotime('2015/08/30 12:34:56');
$obj = new \test\db\DateFormat();
$obj->ts($date);
$obj->num(40);
$obj->save();

eq(4,sizeof(\test\db\DateFormat::find_sum_by('num','ts')));

eq(2,sizeof(\test\db\DateFormat::find_sum_by('num','ts',Q::date_format('ts','Ym'))));

