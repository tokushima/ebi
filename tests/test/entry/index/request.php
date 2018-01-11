<?php
$b = b();

$b->vars('filebase64',base64_encode(\ebi\Util::file_read(\testman\Resource::path('testdata.txt'))));
$b->vars('filebase64_fail','#######');
$b->file_vars('file',\testman\Resource::path('testdata.txt'));
$b->do_post('index::request');
eq(200,$b->status());

eq(null,$b->json('result/get_file_base64_fail')); // base64ではないのでファイルにならない
eq('#######',$b->json('result/filebase64_fail')); // ファイルに変化しなかったのでそのまま

neq(null,$b->json('result/get_file_base64/name')); // ファイルに変化する

try{
	$b->json('result/filebase64');
	fail('ファイルに変化するとなくなる');
}catch(\testman\NotFoundException $e){	
}

eq('testdata.txt',$b->json('result/get_file/name'));
eq(0,$b->json('result/get_cookie'));




$b->do_post('index::request');
eq(1,$b->json('result/get_cookie'));


$b->do_post('index::request');
eq(2,$b->json('result/get_cookie'));

