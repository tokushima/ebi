<?php
use \ebi\Q;

// dotless 再利用の実証: `from: [['bval', ChainE::class, 'bkey']]` は bval の結合を辿り
// その値列 bval をキーに chain_e へ繋ぐ（chain_b.bval = chain_e.bkey）。cond を書かず成立する。

\test\db\ChainAttr::find_delete();
\test\db\ChainB::find_delete();
\test\db\ChainC::find_delete();
\test\db\ChainE::find_delete();

$c1 = (new \test\db\ChainC())->cval('C-ONE')->save();

$b1 = (new \test\db\ChainB())->c_ref($c1->id())->bval('B-ONE')->save();
$b2 = (new \test\db\ChainB())->c_ref($c1->id())->bval('B-TWO')->save();

(new \test\db\ChainE())->bkey('B-ONE')->eval('E-ONE')->save();
(new \test\db\ChainE())->bkey('B-TWO')->eval('E-TWO')->save();

$a1 = (new \test\db\ChainAttr())->b_ref($b1->id())->save();
$a2 = (new \test\db\ChainAttr())->b_ref($b2->id())->save();

// bval の値経由で chain_e.eval を引ける（dotless 再利用）
eq('E-ONE',\test\db\ChainAttr::find_get(Q::eq('id',$a1->id()))->eval_from());
eq('E-TWO',\test\db\ChainAttr::find_get(Q::eq('id',$a2->id()))->eval_from());

eq(1,sizeof(\test\db\ChainAttr::find_all(Q::eq('eval_from','E-ONE'))));
eq(0,sizeof(\test\db\ChainAttr::find_all(Q::eq('eval_from','E-NONE'))));
