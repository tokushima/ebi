<?php
namespace test\db;
use \ebi\Attribute\Prop;
class Boolean extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?bool $value = null;
}
