<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * 前方参照の検証: 「再利用する側」(cval_from/eval_from) を、参照される結合プロパティ (bval) より
 * 先に宣言している。順序非依存化により requeue で遅延解決され、宣言順に関係なく成立する。
 * ChainB, ChainC, ChainE テーブルが先に必要。
 */
class ChainFwd extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;

	#[Prop]
	protected ?int $b_ref = null;

	// dotted 前方参照: bval の結合を辿り aaa 相当(chain_b)の c_ref → chain_c.id（bval は下で宣言）
	#[Prop(from: [['bval.c_ref', ChainC::class, 'id']], column:'cval')]
	protected ?string $cval_from = null;

	// dotless 前方参照: bval の値列をキーに chain_e.bkey へ（bval は下で宣言）
	#[Prop(from: [['bval', ChainE::class, 'bkey']], column:'eval')]
	protected ?string $eval_from = null;

	// 参照される結合プロパティ（宣言はこの通り後ろ）
	#[Prop(from: [['b_ref', ChainB::class, 'id']], column:'bval')]
	protected ?string $bval = null;
}
