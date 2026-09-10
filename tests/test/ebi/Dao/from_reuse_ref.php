<?php
use \ebi\Q;

// from: の先頭ホップに結合プロパティ参照(`bval.c_ref`)を置く再利用記法。
// cond を一切書かず from だけで、bval の結合(chain_b)を辿って chain_c.cval を引けることを検証する。

\test\db\ChainAttr::find_delete();
\test\db\ChainB::find_delete();
\test\db\ChainC::find_delete();

$c1 = (new \test\db\ChainC())->cval('C-ONE')->save();
$c2 = (new \test\db\ChainC())->cval('C-TWO')->save();

$b1 = (new \test\db\ChainB())->c_ref($c1->id())->bval('B-ONE')->save();
$b2 = (new \test\db\ChainB())->c_ref($c2->id())->bval('B-TWO')->save();

$a1 = (new \test\db\ChainAttr())->b_ref($b1->id())->save();
$a2 = (new \test\db\ChainAttr())->b_ref($b2->id())->save();

$a1r = \test\db\ChainAttr::find_get(Q::eq('id',$a1->id()));
eq('C-ONE',$a1r->cval_from());

$a2r = \test\db\ChainAttr::find_get(Q::eq('id',$a2->id()));
eq('C-TWO',$a2r->cval_from());

eq(1,sizeof(\test\db\ChainAttr::find_all(Q::eq('cval_from','C-ONE'))));
eq(1,sizeof(\test\db\ChainAttr::find_all(Q::eq('cval_from','C-TWO'))));
eq(0,sizeof(\test\db\ChainAttr::find_all(Q::eq('cval_from','C-NONE'))));
