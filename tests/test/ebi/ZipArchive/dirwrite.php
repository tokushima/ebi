<?php
// ディレクトリ
$zipfile = \ebi\WorkingStorage::tmpdir();

try{
	$arc = new \ebi\ZipArchive($zipfile);
	fail();
}catch (\ebi\exception\AccessDeniedException $e){
	// ディレクトリには書き込めない
}



