<?php
use \ebi\Q;

// dotless 再利用の実証: `from: [['bval', ChainE::class, 'bkey']]` は bval の結合を辿り
// その値列 bval をキーに chain_e へ繋ぐ（chain_b.bval = chain_e.bkey）。cond を書かず成立する。

\test\db\ChainDotless::find_delete();
\test\db\ChainB::find_delete();
\test\db\ChainE::find_delete();

$b1 = (new \test\db\ChainB())->c_ref(0)->bval('B-ONE')->save();
$b2 = (new \test\db\ChainB())->c_ref(0)->bval('B-TWO')->save();

(new \test\db\ChainE())->bkey('B-ONE')->eval('E-ONE')->save();
(new \test\db\ChainE())->bkey('B-TWO')->eval('E-TWO')->save();

$a1 = (new \test\db\ChainDotless())->b_ref($b1->id())->save();
$a2 = (new \test\db\ChainDotless())->b_ref($b2->id())->save();

eq('E-ONE',\test\db\ChainDotless::find_get(Q::eq('id',$a1->id()))->eval_from());
eq('E-TWO',\test\db\ChainDotless::find_get(Q::eq('id',$a2->id()))->eval_from());

eq(1,sizeof(\test\db\ChainDotless::find_all(Q::eq('eval_from','E-ONE'))));
eq(0,sizeof(\test\db\ChainDotless::find_all(Q::eq('eval_from','E-NONE'))));
