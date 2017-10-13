<?php
use \ebi\Q;
\test\db\Find::find_delete();


(new \test\db\Find())->order(1)->value1('aaa')->value2('ZZZ')->save();
(new \test\db\Find())->order(1)->value1('bbb')->value2('ZZZ')->save();
(new \test\db\Find())->order(1)->value1('ccc')->value2('ZZZ')->save();
(new \test\db\Find())->order(1)->value1('ddd')->value2('ZZZ')->save();
(new \test\db\Find())->order(1)->value1('eee')->value2('ZZZ')->save();

(new \test\db\Find())->order(1)->value1('aaa')->value2('YYY')->save();
(new \test\db\Find())->order(1)->value1('bbb')->value2('YYY')->save();
(new \test\db\Find())->order(1)->value1('ccc')->value2('YYY')->save();
(new \test\db\Find())->order(1)->value1('ddd')->value2('YYY')->save();
(new \test\db\Find())->order(1)->value1('eee')->value2('YYY')->save();

(new \test\db\Find())->order(1)->value1('aaa')->value2('TTT')->save();
(new \test\db\Find())->order(1)->value1('bbb')->value2('TTT')->save();
(new \test\db\Find())->order(1)->value1('ccc')->value2('TTT')->save();
(new \test\db\Find())->order(1)->value1('ddd')->value2('TTT')->save();
(new \test\db\Find())->order(1)->value1('eee')->value2('TTT')->save();


eq(10,\test\db\Find::find_count(
	Q::ob(Q::eq('value2','ZZZ'),Q::eq('value2','TTT'))
));


try{
	\test\db\Find::find_count(
		Q::ob(Q::eq('value1','aaa'))
	);
	fail();
}catch(\ebi\exception\InvalidArgumentException $e){
}


eq(9,\test\db\Find::find_count(
	Q::ob(Q::eq('value1','aaa'),Q::eq('value1','ccc'),Q::eq('value1','eee'))
));



// value1 = 'aaa' or value1 = 'ccc' or value1 = 'eee' or value2 = 'TTT' or value2 = 'ZZZ'
eq(13,\test\db\Find::find_count(
	Q::ob(Q::eq('value1','aaa'),Q::eq('value1','ccc'),Q::eq('value1','eee'),Q::eq('value2','TTT'),Q::eq('value2','ZZZ'))
));


// (value1 = 'aaa' or value1 = 'ccc' or value1 = 'eee') and (value2 = 'TTT' or value2 = 'ZZZ')
$cnt = \test\db\Find::find_count(
	Q::ob(Q::eq('value1','aaa'),Q::eq('value1','ccc'),Q::eq('value1','eee')),
	Q::ob(Q::eq('value2','TTT'),Q::eq('value2','ZZZ'))
);
eq(6,$cnt);


$q = new Q();
$q->add(Q::ob(Q::eq('value1','aaa'),Q::eq('value1','ccc'),Q::eq('value1','eee')));
$q->add(Q::ob(Q::eq('value2','TTT'),Q::eq('value2','ZZZ')));
$cnt = \test\db\Find::find_count($q);
eq(6,$cnt);


$q = new Q();
$q->add(
	Q::ob(
		Q::eq('value1','aaa'),
		Q::eq('value1','ccc'),
		Q::eq('value1','eee'),
		Q::ob(Q::eq('value2','TTT'),Q::eq('value2','ZZZ'))
	)
);
$cnt = \test\db\Find::find_count($q);
eq(13,$cnt);












