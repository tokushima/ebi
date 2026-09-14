<?php
namespace test\db;
use \ebi\Attribute\Prop;
class JoinC extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?int $a_id = null;
	protected ?int $b_id = null;
}
