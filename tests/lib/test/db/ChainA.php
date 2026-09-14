<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * ChainB, ChainC テーブルが先に必要。
 * bval は結合プロデューサ（chain_b への join）。cval_at / cval_bare は bval の結合を辿る2プロパティで、
 * 同一の結合結果になる（1つの producer 結合を複数プロパティが再利用できることの実証）。
 */
class ChainA extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?int $b_ref = null;
	#[Prop(from: [['b_ref', ChainB::class, 'id']], column:'bval')]
	protected ?string $bval = null;
	#[Prop(from: [['bval.c_ref', ChainC::class, 'id']], column:'cval')]
	protected ?string $cval_at = null;
	#[Prop(from: [['bval.c_ref', ChainC::class, 'id']], column:'cval')]
	protected ?string $cval_bare = null;
}
