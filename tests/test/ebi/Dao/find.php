<?php
use \ebi\Q;
\test\db\Find::find_delete();

$abc = (new \test\db\Find())->order(4)->value1('abc')->value2('ABC')->save();
$def = (new \test\db\Find())->order(3)->value1('def')->value2('DEF')->save();
$ghi = (new \test\db\Find())->order(1)->value1('ghi')->value2('GHI')->updated('2008/12/24 10:00:00')->save();
$jkl = (new \test\db\Find())->order(2)->value1('jkl')->value2('EDC')->save();
$aaa = (new \test\db\Find())->order(2)->value1('aaa')->value2('AAA')->updated('2008/12/24 10:00:00')->save();
$bbb = (new \test\db\Find())->order(2)->value1('bbb')->value2('Aaa')->save();
$ccc = (new \test\db\Find())->order(2)->value1('ccc')->value2('aaa')->save();
$mno = (new \test\db\Find())->order(2)->value1('mno')->value2(null)->save();

eq(8,\test\db\Find::find_count());
eq(8,sizeof(\test\db\Find::find_all()));
eq(5,sizeof(\test\db\Find::find_all(Q::eq('order',2))));

eq(3,sizeof(\test\db\Find::find_all(Q::eq('order',2),Q::eq('value2','aaa',Q::IGNORE))));


$sub1 = (new \test\db\SubFind())->value('abc')->order(4)->save();
$sub2 = (new \test\db\SubFind())->value('def')->order(3)->save();
$sub3 = (new \test\db\SubFind())->value('ghi')->order(1)->save();
$sub4 = (new \test\db\SubFind())->value('jkl')->order(2)->save();
$sub5 = (new \test\db\SubFind())->value('@@@')->order(5)->save();


eq(4,sizeof(
		\test\db\Find::find_all(
			Q::in('value1',\test\db\SubFind::find_sub('value'))
		)
	)
);

eq(3,sizeof(
		\test\db\Find::find_all(
			Q::in('value1',\test\db\SubFind::find_sub('value',Q::gte('order',2)))
		)
	)
);

eq(0,sizeof(
		\test\db\Find::find_all(
			Q::in('value1',\test\db\SubFind::find_sub('value',Q::gte('order',100))) // inの対象がサブクエリの結果：空であれば対象なしとなる(SQLの動作)
		)
	)
);

eq(8,sizeof(
		\test\db\Find::find_all(
			Q::in('value1',[]) // inの対象が値で空だと条件がないのと同等 = 全件が対象となる(SQLの動作)
		)
	)
);


eq(0,sizeof(
		\test\db\Find::find_all(
			Q::in('value1',\test\db\SubFind::find_sub('value',Q::gte('order',5))) // Findにはない
		)
	)
);



// -- 
\test\db\RefFind::find_delete();
$ref1 = (new \test\db\RefFind())->parent_id($abc->id())->save();
$ref2 = (new \test\db\RefFind())->parent_id($def->id())->save();
$ref3 = (new \test\db\RefFind())->parent_id($ghi->id())->save();
$ref4 = (new \test\db\RefFind())->parent_id($jkl->id())->save();
eq(4,sizeof(\test\db\RefFind::find_all()));
eq(1,sizeof(\test\db\RefFind::find_all(Q::eq('value','def'))));
eq(1,sizeof(\test\db\RefFind::find_all(Q::eq('value2','EDC'))));

eq(1,sizeof(\test\db\RefFindExt::find_all(Q::eq('value','def'))));
eq(1,sizeof(\test\db\RefFindExt::find_all(Q::eq('value2','EDC'))));
eq(1,sizeof(\test\db\RefFindExt::find_all(Q::eq('order',3))));

eq(4,sizeof(\test\db\HasFind::find_all()));
$has1 = \test\db\HasFind::find_get(Q::eq('parent_id',$ref3->parent_id()));
if(eq(true,($has1->parent() instanceof \test\db\Find))){
	eq('ghi',$has1->parent()->value1());
}

\test\db\RefRefFind::find_delete();
$refref1 = (new \test\db\RefRefFind())->parent_id($ref1->id())->save();
$refref2 = (new \test\db\RefRefFind())->parent_id($ref2->id())->save();
$refref3 = (new \test\db\RefRefFind())->parent_id($ref3->id())->save();
eq(3,sizeof(\test\db\RefRefFind::find_all()));
eq(1,sizeof(\test\db\RefRefFind::find_all(Q::eq('value','def'))));




foreach(\test\db\Find::find(Q::eq('value1','abc')) as $obj){
	eq('abc',$obj->value1());
}
foreach(\test\db\AbcFind::find() as $obj){
	eq('abc',$obj->value1());
}

eq(8,\test\db\Find::find_count());
eq(8,\test\db\Find::find_count('value1'));
eq(7,\test\db\Find::find_count('value2'));
eq(5,\test\db\Find::find_count(Q::eq('order',2)));
eq(4,\test\db\Find::find_count(
		Q::neq('value1','abc'),
		Q::ob(
				Q::b(Q::eq('order',2)),
				Q::b(Q::eq('order',4))
		),
		Q::neq('value1','aaa')
));
$q = new Q();
$q->add(Q::neq('value1','abc'));
$q->add(Q::ob(
		Q::b(Q::eq('order',2)),
		Q::b(Q::eq('order',4))
));
$q->add(Q::neq('value1','aaa'));
eq(4,\test\db\Find::find_count($q));

$q = new Q();
$q->add(Q::ob(
	Q::b(
		Q::eq('order',2),
		Q::ob(
			Q::b(Q::eq('value1','ccc')),
			Q::b(Q::eq('value2','EDC'))
		)
	),
	Q::b(Q::eq('order',4))
));
eq(3,\test\db\Find::find_count($q));


$paginator = new \ebi\Paginator(1,2);
eq(1,sizeof($result = \test\db\Find::find_all(Q::neq('value1','abc'),$paginator)));
eq('ghi',$result[0]->value1());
eq(7,$paginator->total());

$i = 0;
foreach(\test\db\Find::find(
		Q::neq('value1','abc'),
		Q::ob(
				Q::b(Q::eq('order',2)),
				Q::b(Q::eq('order',4))
		),
		Q::neq('value1','aaa')
) as $obj){
	$i++;
}
eq(4,$i);

$list = array('abc','def','ghi','jkl','aaa','bbb','ccc','mno');
$i = 0;
foreach(\test\db\Find::find() as $obj){
	eq($list[$i],$obj->value1());
	$i++;
}
$b = false;
foreach(\test\db\Find::find(Q::eq('value1','AbC',Q::IGNORE)) as $obj){
	eq('abc',$obj->value1());
	$b = true;
}
eq(true,$b);
foreach(\test\db\Find::find(Q::neq('value1','abc')) as $obj){
	neq('abc',$obj->value1());
}


// like 

// 対象が空の場合は無視
eq(8,\test\db\Find::find_count(Q::startswith('value1',[])));

// 対象が空の場合は無視
eq(8,\test\db\Find::find_count(Q::endswith('value1',[])));

// 対象が空の場合は無視
eq(8,\test\db\Find::find_count(Q::contains('value1',[])));


$i = 0;
$r = array('aaa','bbb','ccc');
foreach(\test\db\Find::find(Q::startswith('value1,value2',array('aa'),Q::IGNORE)) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
eq(3,$i);

$i = 0;
$r = array('abc','jkl','ccc');
foreach(\test\db\Find::find(Q::endswith('value1,value2',array('c'),Q::IGNORE)) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
eq(3,$i);

$i = 0;
$r = array('abc','bbb');
foreach(\test\db\Find::find(Q::contains('value1,value2',array('b'))) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
eq(2,$i);

$i = 0;
$r = array('abc','jkl','ccc');
foreach(\test\db\Find::find(Q::endswith('value1,value2',array('C'),Q::IGNORE)) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
	$t[] = $obj->value1();
}
eq(3,$i);


// in 

// 対象が空の場合は無視
eq(8,\test\db\Find::find_count(Q::in('value1',[])));

$i = 0;
foreach(\test\db\Find::find(Q::in('value1',array('abc'))) as $obj){
	eq('abc',$obj->value1());
	$i++;
}
eq(1,$i);

foreach(\test\db\Find::find(Q::match('value1=abc')) as $obj){
	eq('abc',$obj->value1());
}
foreach(\test\db\Find::find(Q::match('value1=!abc')) as $obj){
	neq('abc',$obj->value1());
}
foreach(\test\db\Find::find(Q::match('abc')) as $obj){
	eq('abc',$obj->value1());
}
$i = 0;
$r = array('aaa','bbb','mno');
foreach(\test\db\Find::find(Q::neq('value1','ccc'),new \ebi\Paginator(1,3),Q::order('-id')) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
foreach(\test\db\Find::find(Q::neq('value1','abc'),new \ebi\Paginator(1,3),Q::order('id')) as $obj){
	eq('jkl',$obj->value1());
}
$i = 0;
$r = array('mno','aaa');
foreach(\test\db\Find::find(Q::neq('value1','ccc'),new \ebi\Paginator(1,2),Q::order('order,-id')) as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value1());
	$i++;
}
$result = \test\db\Find::find_all(Q::match('AAA'));
eq(3,sizeof($result));

$result = \test\db\Find::find_all(Q::match('AA'));
eq(3,sizeof($result));

$result = \test\db\Find::find_all(Q::eq('value2',null));
eq(1,sizeof($result));
$result = \test\db\Find::find_all(Q::neq('value2',null));
eq(7,sizeof($result));

$result = \test\db\Find::find_all(Q::eq('updated',null));
eq(6,sizeof($result));
$result = \test\db\Find::find_all(Q::neq('updated',null));
eq(2,sizeof($result));
eq('2008-12-24T10:00:00+09:00',$result[0]->fm_updated());

$c = 0;
for($i=0;$i<10;$i++){
	$a = $b = [];
	foreach(\test\db\Find::find_all(Q::random_order()) as $o) $a[] = $o->id();
	foreach(\test\db\Find::find_all(Q::random_order()) as $o) $b[] = $o->id();
	if($a === $b) $c++;
}
neq(10,$c);


$result = \test\db\Find::find_all(Q::ob(
		Q::b(Q::eq('value1','abc'))
		,Q::b(Q::eq('value2','EDC'))
));
eq(2,sizeof($result));

eq('EDC',\test\db\Find::find_get(Q::eq('value1','jkl'))->value2());

$i = 0;
$r = array('abc','def','ghi','jkl');
foreach(\test\db\RefFind::find() as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value());
	$i++;
}
eq(4,$i);

$i = 0;
$r = array('abc','def','ghi');
foreach(\test\db\RefRefFind::find() as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->value());
	$i++;
}
eq(3,$i);


$i = 0;
$r = array('abc','def','ghi','jkl');
foreach(\test\db\HasFind::find() as $obj){
	eq(isset($r[$i]) ? $r[$i] : null,$obj->parent()->value1());
	$i++;
}
eq(4,$i);


$result = \test\db\Find::find_all(Q::in('value1',\test\db\SubFind::find_sub('value')));
eq(4,sizeof($result));
$result = \test\db\Find::find_all(Q::in('value1',\test\db\SubFind::find_sub('value',Q::lt('order',3))));
eq(2,sizeof($result));



