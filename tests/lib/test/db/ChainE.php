<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * bval の「値」をキーに繋ぐ先（chain_b.bval = chain_e.bkey）。dotless 再利用の実証用。
 */
class ChainE extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $bkey = null;
	protected ?string $eval = null;
}
