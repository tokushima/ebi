<?php
// 新規ファイル
$zipfile = \ebi\WorkingStorage::path(time().'.zip');

$arc = new \ebi\ZipArchive($zipfile);
$arc->add($tmpdir);
$arc->write();

// 確認
$outpath = \ebi\ZipArchive::extract($zipfile);
foreach($test_file_list as $filename){
	eq(true,is_file($outpath.$filename));
	eq($filename,file_get_contents($outpath.$filename));
}


