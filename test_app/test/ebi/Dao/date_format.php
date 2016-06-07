<?php
use \ebi\Q;

\test\db\DateFormat::create_table();
\test\db\DateFormat::find_delete();

$date = strtotime('2015/07/04 12:34:56');
$obj = new \test\db\DateFormat();
$obj->ts($date);
$obj->num(10);
$obj->save();

foreach(\test\db\DateFormat::find() as $o){
	eq(date('Y/m/d H:i:s',$date),$o->fm_ts());
}

foreach(\test\db\DateFormat::find(Q::date_format('ts','Ym')) as $o){
	eq(date('Y/m/01 00:00:00',$date),$o->fm_ts());
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
	eq(date('Y/m/01 00:00:00',$date),$o->fm_ts());
}

$date = strtotime('2015/08/30 12:34:56');
$obj = new \test\db\DateFormat();
$obj->ts($date);
$obj->num(40);
$obj->save();

eq(4,sizeof(\test\db\DateFormat::find_sum_by('num','ts')));

eq(2,sizeof(\test\db\DateFormat::find_sum_by('num','ts',Q::date_format('ts','Ym'))));

