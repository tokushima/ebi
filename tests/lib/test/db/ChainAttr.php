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

	#[Prop(cond:'b_ref(chain_b.id)', column:'bval')]
	protected ?string $bval = null;

	#[Prop(from: [['bval.c_ref', ChainC::class, 'id']], column:'cval')]
	protected ?string $cval_from = null;

	// dotless 再利用: bval の結合を辿り、その「値列 bval」をキーに chain_e へ繋ぐ（chain_b.bval = chain_e.bkey）。
	// `bval(chain_e.bkey)` へ desugar され、bare 正規化で `@bval(chain_e.bkey)` と等価になる。
	#[Prop(from: [['bval', ChainE::class, 'bkey']], column:'eval')]
	protected ?string $eval_from = null;
}
