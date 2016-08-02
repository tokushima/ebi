<?php
\test\db\AutoCodePrefix::create_table();
\test\db\AutoCodePrefix::find_delete();



$codebase = 'ABCDEFGHJKLMNPQRSTUVWXY0123456789';
$time = time();

$code = \ebi\Code::encode($codebase,date('Y',$time)).
		\ebi\Code::encode($codebase,date('m',$time)).
		\ebi\Code::encode($codebase,date('d',$time)).
		\ebi\Code::encode($codebase,date('H',$time));


$obj = new \test\db\AutoCodePrefix();
$obj->save();



eq($code,substr($obj->code(),0,6));
