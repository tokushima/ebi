<?php

$zipfile = \ebi\WorkingStorage::path('archive.zip');
$outpath = \ebi\WorkingStorage::path('archive');


$arc = new \ebi\Archive();
$arc->add(__FILE__,dirname(__DIR__));
$arc->add(__FILE__,dirname(dirname(__DIR__)));
$arc->zipwrite($zipfile);


\ebi\Archive::unzip($zipfile, $outpath);
eq(true,is_file($outpath.'/ebi/Archive.php'));
eq(file_get_contents(__FILE__),file_get_contents($outpath.'/ebi/Archive.php'));
eq(file_get_contents(__FILE__),file_get_contents($outpath.'/test/ebi/Archive.php'));
