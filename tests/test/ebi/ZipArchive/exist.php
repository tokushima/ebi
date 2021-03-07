<?php
// 空ファイル
$zipfile = \ebi\WorkingStorage::tmpfile();

$arc = new \ebi\ZipArchive($zipfile);
$arc->add($tmpdir);
$arc->write(); // 新規

// ZIPに追加
$append_tmpdir = \ebi\WorkingStorage::tmpdir();
$append_test_file_list = [
	'/abc',
	'/XYZDIR/xyz',
];
foreach($append_test_file_list as $filename){
	\ebi\Util::file_write($append_tmpdir.$filename,$filename);
}
$arc = new \ebi\ZipArchive($zipfile, true);
$arc->add($append_tmpdir);
$arc->write(); // 追加

// 確認
$outpath = \ebi\ZipArchive::extract($zipfile);
foreach(array_merge($test_file_list,$append_test_file_list) as $filename){
	eq(true,is_file($outpath.$filename));
	eq($filename,file_get_contents($outpath.$filename));
}


// 新規で上書き
$arc = new \ebi\ZipArchive($zipfile);
$arc->add($tmpdir);
$arc->write(); // 新規

// 確認
$outpath = \ebi\ZipArchive::extract($zipfile);
foreach($test_file_list as $filename){
	eq(true,is_file($outpath.$filename));
	eq($filename,file_get_contents($outpath.$filename));
}

// 無い
foreach($append_test_file_list as $filename){
	eq(false,is_file($outpath.$filename));
}



