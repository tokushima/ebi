<?php
$b = b();

$b->do_post('index::file_upload');
$b->has_error('RequiredException');


$tmp = \ebi\WorkingStorage::tmpfile(str_repeat('A',1024));
$b->file_vars('file1',$tmp);
$b->do_post('index::file_upload');
$b->find_get('result');

$tmp = \ebi\WorkingStorage::tmpfile(str_repeat('A',2048));
$b->file_vars('file1',$tmp);
$b->do_post('index::file_upload');
$b->has_error('MaxSizeExceededException');






