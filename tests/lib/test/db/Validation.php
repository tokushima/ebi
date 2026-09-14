<?php
namespace test\db;
use \ebi\Attribute\Prop;
class Validation extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(max:2)]
	protected ?string $value = null;
}
