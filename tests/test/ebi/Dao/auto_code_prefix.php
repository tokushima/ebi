<?php
\test\db\AutoCodePrefix::create_table();
\test\db\AutoCodePrefix::find_delete();



$codebase = 'abcdefghjkmnprstuvwxy0123456789';
$time = time();

$code = \ebi\Code::encode($codebase,date('Y',$time)-1).
		\ebi\Code::encode($codebase,date('m',$time)-1).
		\ebi\Code::encode($codebase,date('d',$time)-1).
		\ebi\Code::encode($codebase,date('H',$time));


$obj = new \test\db\AutoCodePrefix();
$obj->save();



eq($code,substr($obj->code(),0,6));
