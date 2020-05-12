<?php
// 新規ファイル
$zipfile = \ebi\WorkingStorage::path(time().'.zip');

$arc = new \ebi\Archive($tmpdir);
$arc->zipwrite($zipfile);

// 確認
$outpath = \ebi\Archive::unzip($zipfile);
foreach($test_file_list as $filename){
	eq(true,is_file($outpath.$filename));
	eq($filename,file_get_contents($outpath.$filename));
}


