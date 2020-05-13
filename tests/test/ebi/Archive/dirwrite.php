<?php
// ディレクトリ
$zipfile = \ebi\WorkingStorage::tmpdir();

$arc = new \ebi\Archive($tmpdir);

try{
	$arc->zipwrite($zipfile);
	fail();
}catch (\ebi\exception\AccessDeniedException $e){
	// ディレクトリには書き込めない
}



