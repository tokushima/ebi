<?php
$b = new \ebi\Browser();
$b->vars('abc',123);
$b->vars('def',456);
$b->do_json(\testman\Util::url('index::http_method_vars').'?xyz=789');
eq('{"abc":123,"def":456}',\ebi\Json::decode($b->body())['result']['raw']);


$b = new \ebi\Browser();
$b->vars('abc',123);
$b->vars('def',456);
$b->do_post(\testman\Util::url('index::http_method_vars').'?xyz=789');
eq(123,$b->json('result/post/abc'));
eq(456,$b->json('result/post/def'));

try{
	$b->json('result/post/xyz');
	fail();
}catch(\ebi\exception\NotFoundException $e){
}

// 指定がなければ最初のform
$b = new \ebi\Browser();
$b->do_get(\testman\Util::url('index::browser_form'));
$form_info = $b->form();

\ebi\Browser::start_record();
	$b->do_get($form_info['action']);
$record = \ebi\Browser::stop_record();
meq('id=ID1&hid_nm1=abc&txtarea1=BBBBB&slc1=B&r1=2&c1%5B0%5D=2',array_shift($record));


// 指定のform
$b = new \ebi\Browser();
$b->do_get(\testman\Util::url('index::browser_form'));
$form_info = $b->form('frm1');

\ebi\Browser::start_record();
$b->do_get($form_info['action']);
$record = \ebi\Browser::stop_record();
meq('ID2&hdn1=AAA&hdn2=BBB',array_shift($record));



// set=falseならセットされない
$b = new \ebi\Browser();
$b->do_get(\testman\Util::url('index::browser_form'));
$form_info = $b->form('frm1',false);

\ebi\Browser::start_record();
$b->do_get($form_info['action']);
$record = \ebi\Browser::stop_record();
mneq('ID2&hdn1=AAA&hdn2=BBB',array_shift($record));
