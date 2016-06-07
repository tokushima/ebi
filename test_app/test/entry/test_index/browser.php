<?php
$b = new \ebi\Browser();
$b->vars('abc',123);
$b->vars('def',456);
$b->do_json(url('test_index::http_method_vars').'?xyz=789');
eq('{"abc":123,"def":456}',\ebi\Json::decode($b->body())['result']['raw']);


$b = new \ebi\Browser();
$b->vars('abc',123);
$b->vars('def',456);
$b->do_post(url('test_index::http_method_vars').'?xyz=789');
eq(123,$b->json('result/post/abc'));
eq(456,$b->json('result/post/def'));

try{
	$b->json('result/post/xyz');
	fail();
}catch(\ebi\exception\NotFoundException $e){
}


$b = new \ebi\Browser();
$b->vars('abc',123);
$b->vars('def',456);
$b->do_get(url('test_index::http_method_vars').'?xyz=789');
eq(123,$b->json('result/get/abc'));
eq(456,$b->json('result/get/def'));
eq(789,$b->json('result/get/xyz'));

