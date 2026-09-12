<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * ChainB, ChainC テーブルが先に必要。
 * bval は結合プロデューサ（chain_b への join）。cval_from は `from:` の先頭ホップに
 * `bval.c_ref`（＝bval の結合列 c_ref）を置き、`bval.c_ref(chain_c.id)` へ desugar される。
 * bare 正規化で `@bval.c_ref(...)` と同義になり、cond を書かず from だけで結合再利用を表現できる。
 */
class ChainAttr extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;

	#[Prop]
	protected ?int $b_ref = null;

	#[Prop(from: [['b_ref', ChainB::class, 'id']], column:'bval')]
	protected ?string $bval = null;

	#[Prop(from: [['bval.c_ref', ChainC::class, 'id']], column:'cval')]
	protected ?string $cval_from = null;
}
