<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * dotless 再利用の検証用（通常の宣言順）。ChainB, ChainE テーブルが先に必要。
 * bval の結合を辿り、その「値列 bval」をキーに chain_e へ繋ぐ（chain_b.bval = chain_e.bkey）。
 * `bval(chain_e.bkey)` へ desugar され、正規化で `@bval(chain_e.bkey)` と等価になる。
 */
class ChainDotless extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;

	#[Prop]
	protected ?int $b_ref = null;

	#[Prop(from: [['b_ref', ChainB::class, 'id']], column:'bval')]
	protected ?string $bval = null;

	#[Prop(from: [['bval', ChainE::class, 'bkey']], column:'eval')]
	protected ?string $eval_from = null;
}
