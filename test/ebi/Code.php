<?php
$codebase = '0123456789ABC';


$max = \ebi\Code::max($codebase,5);
$maxcode = \ebi\Code::encode($codebase,$max);
eq('CCCCC',$maxcode);
eq($max,\ebi\Code::decode($codebase, $maxcode));


$min = \ebi\Code::min($codebase,5);
$mincode = \ebi\Code::encode($codebase,$min);
eq('10000',$mincode);
eq($min,\ebi\Code::decode($codebase, $mincode));


eq(3,strlen(\ebi\Code::rand($codebase,3)));


