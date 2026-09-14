<?php
namespace test\db;
use \ebi\Attribute\Prop;
class LimitVerify extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(max:3, min:2)]
	protected ?string $value1 = null;
	#[Prop(max:3, min:2)]
	protected ?int $value2 = null;
}
