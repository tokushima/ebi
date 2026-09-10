<?php
namespace test\db;
/**
 * ChainB, ChainC テーブルが先に必要。
 * bval は結合プロデューサ（chain_b への join）。cval_at は @ 参照でその join を辿り、
 * cval_bare は同じ道筋を @ 無しの bare で書く（@ 省略記法）。両者は同一の結合結果になる。
 * @var serial $id
 * @var int $b_ref
 * @var string $bval @['cond'=>'b_ref(chain_b.id)','column'=>'bval']
 * @var string $cval_at @['cond'=>'@bval.c_ref(chain_c.id)','column'=>'cval']
 * @var string $cval_bare @['cond'=>'bval.c_ref(chain_c.id)','column'=>'cval']
 */
class ChainA extends \ebi\Dao{
	protected $id;
	protected $b_ref;
	protected $bval;
	protected $cval_at;
	protected $cval_bare;
}
