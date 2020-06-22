<?php
/**
 * キーを"変数名[]"とすることでクエリストリングで変数名[0]ではなく変数名[]となる
 * @var \ebi\Browser $b
 */
$b = new \ebi\Browser();


// def[]はdef[]
// ghi%5B0%5D=1&ghi%5B2%5D=20&ghi%5B3%5D=300&def%5B%5D=1&def%5B%5D=20&def%5B%5D=300
$result = [
	'result'=>[
		'aaa'=>['1','20','300'],
		'bbb'=>[0=>'1',2=>'20',3=>'300'],
		'ccc'=>['1','20','300'],
	]
];

\ebi\Browser::start_record();
$b->vars('aaa',[1,20,300]);
$b->vars('bbb',[0=>1,2=>20,3=>300]);
$b->vars('ccc[]',[1,20,300]);
$b->do_get(\testman\Util::url('index::request/plain'));
eq($result,$b->json());
$record = \ebi\Browser::stop_record();
meq('?aaa%5B0%5D=1&aaa%5B1%5D=20&aaa%5B2%5D=300&bbb%5B0%5D=1&bbb%5B2%5D=20&bbb%5B3%5D=300&ccc%5B%5D=1&ccc%5B%5D=20&ccc%5B%5D=300',$record[0]);


$b->vars('aaa',[1,20,300]);
$b->vars('bbb',[0=>1,2=>20,3=>300]);
$b->vars('ccc[]',[1,20,300]);
$b->do_post(\testman\Util::url('index::request/plain'));
eq($result,$b->json());

