<?php
namespace test\db;
use \ebi\Attribute\Prop;
class NewDao extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $value = null;
}
