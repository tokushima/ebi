<?php
namespace test\db;
use \ebi\Attribute\Prop;
/**
 * 計算
 */
class Calc extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?int $price = null;
	protected ?string $type = null;
	protected ?string $name = null;
	protected ?float $float = null;
}
