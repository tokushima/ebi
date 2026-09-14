<?php
namespace test\db;
use \ebi\Attribute\Prop;
class JoinB extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $name = null;
}
