<?php
$tmp = \ebi\WorkingStorage::tmpfile(str_repeat('A',1024));

$b = b();

// 必須エラー
$b->do_post('index::file_upload');
$b->has_error('RequiredException');


// 成功
$b->file_vars('file1',$tmp);
$b->do_post('index::file_upload');
$b->find_get('result');

// サイズオーバー
$tmp_over = \ebi\WorkingStorage::tmpfile(str_repeat('A',2048));
$b->file_vars('file1',$tmp_over);
$b->do_post('index::file_upload');
$b->has_error('MaxSizeExceededException');



$b->file_vars('file1',$tmp);
$b->do_post('index::file_upload');
$b->find_get('result');

$files = $b->find_get('result/files');
$tmp_name = $files['file1']['tmp_name'];

// リクエストが終了すると削除されている
eq(!is_file($tmp_name));



