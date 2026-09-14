<?php
namespace test\db;
use \ebi\Attribute\Prop;
class SubFind extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $value = null;
	protected ?int $order = null;
}
