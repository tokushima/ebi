<?php
use \ebi\Q;

$ref = function($o){
	return $o;
};
\test\db\Calc::create_table();
\test\db\Calc::find_delete();


$ref(new \test\db\Calc())->price(30)->type('B')->name('AAA')->save();
$ref(new \test\db\Calc())->price(20)->type('B')->name('ccc')->save();
$ref(new \test\db\Calc())->price(20)->type('A')->name('AAA')->save();
$ref(new \test\db\Calc())->price(10)->type('A')->name('BBB')->save();

eq(80,\test\db\Calc::find_sum('price'));
eq(30,\test\db\Calc::find_sum('price',Q::eq('type','A')));

eq(array('A'=>30,'B'=>50),\test\db\Calc::find_sum_by('price','type'));
eq(array('A'=>30),\test\db\Calc::find_sum_by('price','type',Q::eq('type','A')));

eq(30,\test\db\Calc::find_max('price'));
eq(20,\test\db\Calc::find_max('price',Q::eq('type','A')));
eq('ccc',\test\db\Calc::find_max('name'));
eq('BBB',\test\db\Calc::find_max('name',Q::eq('type','A')));


eq(10,\test\db\Calc::find_min('price'));
eq(20,\test\db\Calc::find_min('price',Q::eq('type','B')));


$result = \test\db\Calc::find_min_by('price','type');
eq(array('A'=>10,'B'=>20),$result);
eq(array('A'=>10),\test\db\Calc::find_min_by('price','type',Q::eq('type','A')));

eq(20,\test\db\Calc::find_avg('price'));
eq(15,\test\db\Calc::find_avg('price',Q::eq('type','A')));

eq(array('A'=>15,'B'=>25),\test\db\Calc::find_avg_by('price','type'));
eq(array('A'=>15),\test\db\Calc::find_avg_by('price','type',Q::eq('type','A')));

eq(array('A','B'),\test\db\Calc::find_distinct('type'));
$result = \test\db\Calc::find_distinct('name',Q::eq('type','A'));
eq(array('AAA','BBB'),$result);


eq(array('A'=>2,'B'=>2),\test\db\Calc::find_count_by('id','type'));
eq(array('AAA'=>2,'BBB'=>1,'ccc'=>1),\test\db\Calc::find_count_by('type','name'));


