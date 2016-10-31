<?php
use \ebi\Q;

\test\db\Calc::create_table();
\test\db\Calc::find_delete();

(new \test\db\Calc())->float(1.5)->type('A')->save();
(new \test\db\Calc())->float(1.3)->type('A')->save();
(new \test\db\Calc())->float(1.1)->type('B')->save();


eq(1.3,\test\db\Calc::find_avg('float'));
eq(1.4,\test\db\Calc::find_avg('float',Q::eq('type','A')));


eq(array('A'=>1.4,'B'=>1.1),\test\db\Calc::find_avg_by('float','type'));
eq(array('A'=>1.4),\test\db\Calc::find_avg_by('float','type',Q::eq('type','A')));



\test\db\Calc::find_delete();
(new \test\db\Calc())->price(1)->type('A')->save();
(new \test\db\Calc())->price(2)->type('A')->save();
(new \test\db\Calc())->price(3)->type('B')->save();


eq(2,\test\db\Calc::find_avg('price'));
eq(1.5,\test\db\Calc::find_avg('price',Q::eq('type','A')));


eq(array('A'=>1.5,'B'=>3),\test\db\Calc::find_avg_by('price','type'));
eq(array('A'=>1.5),\test\db\Calc::find_avg_by('price','type',Q::eq('type','A')));



eq(0,\test\db\Calc::find_avg('type'));
eq(0,\test\db\Calc::find_avg('type',Q::eq('type','A')));

