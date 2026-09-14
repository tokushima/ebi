<?php
namespace test\db;
use \ebi\Attribute\Prop;
class UniqueTripleVerify extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(unique_together:['u2','u3'])]
	protected ?int $u1 = null;
	protected ?int $u2 = null;
	protected ?int $u3 = null;
}
