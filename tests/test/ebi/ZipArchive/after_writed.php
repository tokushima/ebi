<?php
$zipfile = \ebi\WorkingStorage::tmpfile();

$arc = new \ebi\ZipArchive($zipfile);

$tmpfile = \ebi\WorkingStorage::tmpfile('TMPFILE');
$arc->add($tmpfile, 'tmp1.txt');
$wrtitefilename = $arc->write();

$outpath = \ebi\ZipArchive::extract($zipfile);
eq(true,is_file($outpath.'/tmp1.txt'));
eq(false,is_file($outpath.'/tmp2.txt'));

eq($zipfile, $wrtitefilename);


// 追加
$arc->add($tmpfile, 'tmp2.txt');
$wrtitefilename = $arc->write();

$outpath = \ebi\ZipArchive::extract($zipfile);
eq(true,is_file($outpath.'/tmp1.txt'));
eq(true,is_file($outpath.'/tmp2.txt'));



