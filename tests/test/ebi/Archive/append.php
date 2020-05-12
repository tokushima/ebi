<?php
// 空ファイル
$zipfile = \ebi\WorkingStorage::tmpfile();

$arc = new \ebi\Archive($tmpdir);
$arc->zipwrite($zipfile,false); // 新規

// 追加するファイル
$append_tmpdir = \ebi\WorkingStorage::tmpdir();
$append_test_file_list = [
	'/abc',
	'/XYZDIR/xyz',
];
foreach($append_test_file_list as $filename){
	\ebi\Util::file_write($append_tmpdir.$filename,$filename);
}
$arc = new \ebi\Archive($append_tmpdir);
$arc->zipwrite($zipfile,true); // 追加

// 確認
$outpath = \ebi\Archive::unzip($zipfile);
foreach(array_merge($test_file_list,$append_test_file_list) as $filename){
	eq(true,is_file($outpath.$filename));
	eq($filename,file_get_contents($outpath.$filename));
}


