<?php
use \ebi\Q;

// @ 省略記法: paren cond 内の self_var が既出の結合プロパティ名なら @ 参照へ正規化される。
// cval_at(@bval...) と cval_bare(bval...) が同一の結合結果を返すことを検証する。

\test\db\ChainA::find_delete();
\test\db\ChainB::find_delete();
\test\db\ChainC::find_delete();

$c1 = (new \test\db\ChainC())->cval('C-ONE')->save();
$c2 = (new \test\db\ChainC())->cval('C-TWO')->save();

$b1 = (new \test\db\ChainB())->c_ref($c1->id())->bval('B-ONE')->save();
$b2 = (new \test\db\ChainB())->c_ref($c2->id())->bval('B-TWO')->save();

$a1 = (new \test\db\ChainA())->b_ref($b1->id())->save();
$a2 = (new \test\db\ChainA())->b_ref($b2->id())->save();

// 取得値: @ 版・bare 版とも同じ結合先の値を返す
$a1r = \test\db\ChainA::find_get(Q::eq('id',$a1->id()));
eq('C-ONE',$a1r->cval_at());
eq('C-ONE',$a1r->cval_bare());

$a2r = \test\db\ChainA::find_get(Q::eq('id',$a2->id()));
eq('C-TWO',$a2r->cval_at());
eq('C-TWO',$a2r->cval_bare());

// 絞り込み: @ 版・bare 版で同一件数
eq(1,sizeof(\test\db\ChainA::find_all(Q::eq('cval_at','C-ONE'))));
eq(1,sizeof(\test\db\ChainA::find_all(Q::eq('cval_bare','C-ONE'))));
eq(1,sizeof(\test\db\ChainA::find_all(Q::eq('cval_at','C-TWO'))));
eq(1,sizeof(\test\db\ChainA::find_all(Q::eq('cval_bare','C-TWO'))));
eq(0,sizeof(\test\db\ChainA::find_all(Q::eq('cval_bare','C-NONE'))));
