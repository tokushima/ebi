<?php
namespace test\db;
use \ebi\Attribute\Prop;
class ColumnName extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(column:'data')]
	protected ?string $value = null;
}
