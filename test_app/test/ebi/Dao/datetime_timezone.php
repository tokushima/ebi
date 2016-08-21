<?php
\test\db\DateTime::create_table();
\test\db\DateTime::find_delete();

$time = time();

$obj = new \test\db\DateTime();
$obj->ts(date('Y/m/d H:i:s',$time));
$obj->date(date('Y/m/d H:i:s',$time));
$obj->idate(date('Y/m/d H:i:s',$time));
$obj->save();

foreach(\test\db\DateTime::find() as $o){
	eq(date('Y/m/d H:i:s',$time),$o->fm_ts());
	eq(date('Y/m/d',$time),$o->fm_date());
	eq(date('Ymd',$time),$o->idate());
}


$db = \ebi\Dao::connection(\test\db\DateTime::class);

$db->query('select ts,date,idate from date_time');

foreach($db as $data){
	eq(date('Y/m/d H:i:s',$time - (9 * 3600)),$data['ts']);
	eq(date('Y/m/d',$time),$data['date']);
	eq(date('Ymd',$time),$data['idate']);
}


