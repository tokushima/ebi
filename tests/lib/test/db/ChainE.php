<?php
namespace test\db;
/**
 * bval の「値」をキーに繋ぐ先（chain_b.bval = chain_e.bkey）。dotless 再利用の実証用。
 * @var serial $id
 * @var string $bkey
 * @var string $eval
 */
class ChainE extends \ebi\Dao{
	protected $id;
	protected $bkey;
	protected $eval;
}
