<?php
use \ebi\Q;

\test\db\Find::find_delete();

(new \test\db\Find())->order(1)->value1('aaa')->value2('AAA')->updated('2014/10/04')->save();
(new \test\db\Find())->order(3)->value1('ccc')->value2('CCC')->updated('2014/10/06')->save();
(new \test\db\Find())->order(2)->value1('bbb')->value2('BBB')->updated('2014/10/05')->save();


$object_list = \test\db\Find::find_all(Q::order('order'));

eq('<object_list>'.
	'<Find><order>1</order><value1>aaa</value1><value2>AAA</value2><updated>2014-10-04T00:00:00+09:00</updated></Find>'.
	'<Find><order>2</order><value1>bbb</value1><value2>BBB</value2><updated>2014-10-05T00:00:00+09:00</updated></Find>'.
	'<Find><order>3</order><value1>ccc</value1><value2>CCC</value2><updated>2014-10-06T00:00:00+09:00</updated></Find>'.
	'</object_list>',
(new \ebi\Xml('object_list',$object_list))->get());
