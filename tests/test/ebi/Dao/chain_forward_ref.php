<?php
use \ebi\Q;

// 順序非依存: 参照する側(cval_from/eval_from)を参照先(bval)より先に宣言しても、requeue で解決され成立する。

\test\db\ChainFwd::find_delete();
\test\db\ChainB::find_delete();
\test\db\ChainC::find_delete();
\test\db\ChainE::find_delete();

$c1 = (new \test\db\ChainC())->cval('C-ONE')->save();
$b1 = (new \test\db\ChainB())->c_ref($c1->id())->bval('B-ONE')->save();
(new \test\db\ChainE())->bkey('B-ONE')->eval('E-ONE')->save();

$f1 = (new \test\db\ChainFwd())->b_ref($b1->id())->save();
$r  = \test\db\ChainFwd::find_get(Q::eq('id',$f1->id()));

eq('B-ONE',$r->bval());       // 基点の結合
eq('C-ONE',$r->cval_from());  // dotted 前方参照
eq('E-ONE',$r->eval_from());  // dotless 前方参照

// 前方参照列でも絞り込める
eq(1,sizeof(\test\db\ChainFwd::find_all(Q::eq('cval_from','C-ONE'))));
eq(1,sizeof(\test\db\ChainFwd::find_all(Q::eq('eval_from','E-ONE'))));
