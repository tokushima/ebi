<?php
\ebi\Dt::reset_tables();


$obj = new \test\flow\model\Note();
$obj->value('aaabbbbccc');
$obj->save();


$obj = new \test\flow\model\Note();
$obj->value('dddeeeffff');
$obj->save();





