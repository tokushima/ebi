<?php
$zipfile = \ebi\WorkingStorage::tmpfile();

$arc = new \ebi\ZipArchive($zipfile);
$arc->add($tmpdir);


$tmpfile = \ebi\WorkingStorage::tmpfile('TMPFILE');
$arc->add($tmpfile, 'tmpfile.txt');
$arc->add($tmpfile, 'temp1/temp2/tmpfile.txt');


$tmpdir2 = \ebi\WorkingStorage::tmpdir();
$test_file_list2 = [
	'/XXX',
	'/ZZZ',
];
foreach ($test_file_list2 as $filename) {
	\ebi\Util::file_write($tmpdir2 . $filename, $filename);
}
$arc->add($tmpdir2, 'tmpdir');

try {
	$arc->add('notfound');
	fail();
} catch (\ebi\exception\UnknownFileException $e) {
}

$arc->add_from_string('new contents', 'tmpcontents.txt');


$arc->write(); // 新規
\ebi\Util::copy($zipfile, '/Users/tokushima/Downloads/test.zip');

// 確認
$outpath = \ebi\ZipArchive::extract($zipfile);
foreach ($test_file_list as $filename) {
	eq(true, is_file($outpath . $filename));
	eq($filename, file_get_contents($outpath . $filename));
}

foreach ($test_file_list2 as $filename) {
	$file = $outpath . '/tmpdir' . $filename;
	eq(true, is_file($file));
	eq($filename, file_get_contents($file));
}

$file = $outpath . '/tmpfile.txt';
eq(true, is_file($file));
eq('TMPFILE', file_get_contents($file));


$file = $outpath . '/temp1/temp2/tmpfile.txt';
eq(true, is_file($file));
eq('TMPFILE', file_get_contents($file));


$file = $outpath . '/tmpcontents.txt';
eq(true, is_file($file));
eq('new contents', file_get_contents($file));
