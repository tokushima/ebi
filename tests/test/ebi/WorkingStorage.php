<?php

$path = \ebi\WorkingStorage::set('abc','ABC');

eq('ABC',\ebi\WorkingStorage::get('abc'));


$tf = \ebi\WorkingStorage::tmpfile('AAA');
eq(is_file($tf));

$td = \ebi\WorkingStorage::tmpdir();
eq(is_dir($td));


$files = [];
foreach(\ebi\Util::ls(\ebi\Conf::work_path(),true) as $f){
	$files[] = $f->getPathname();
}


$b = b();
$b->vars('value',__FILE__.'A');
$b->do_post('index::working_storage');




$files2 = [];
foreach(\ebi\Util::ls(\ebi\Conf::work_path(),true) as $f){
	$files2[] = $f->getPathname();
}

eq(sizeof($files),sizeof($files2));

eq(is_file($path));

