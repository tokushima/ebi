<?php
/**
 * 
 * @var string $tmpdir
 * @var string[] $test_file_list
 */

$tmpdir = \ebi\WorkingStorage::tmpdir();

$test_file_list = [
	'/aaa',
	'/bbb',
	'/XDIR/xxxx',
	'/XDIR/yyyy',
	'/YDIR/xxxx',
	'/ZDIR/xxxx',
];

foreach($test_file_list as $filename){
	\ebi\Util::file_write($tmpdir.$filename,$filename);
}
