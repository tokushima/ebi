<?php
namespace test\db;
use \ebi\Attribute\Prop;
class JoinA extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
}
