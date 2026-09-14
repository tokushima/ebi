<?php
namespace test\db;
use \ebi\Attribute\Prop;
class DoublePrimary extends \ebi\Dao{
	#[Prop(primary:true)]
	protected ?int $id1 = null;
	#[Prop(primary:true)]
	protected ?int $id2 = null;
	protected ?string $value = null;
}
