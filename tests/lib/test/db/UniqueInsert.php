<?php
namespace test\db;
use \ebi\Attribute\Prop;
class UniqueInsert extends \ebi\Dao{
	#[Prop(primary:true)]
	protected ?string $id = null;
	protected ?string $value = null;
}
