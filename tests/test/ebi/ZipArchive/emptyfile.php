<?php
// 空ファイル
$zipfile = \ebi\WorkingStorage::tmpfile();

$arc = new \ebi\ZipArchive($zipfile);
$arc->add($tmpdir);
$arc->write();

// 確認
$outpath = \ebi\ZipArchive::extract($zipfile);
foreach($test_file_list as $filename){
	eq(true,is_file($outpath.$filename));
	eq($filename,file_get_contents($outpath.$filename));
}


