<?php
namespace test\db;
use \ebi\Attribute\Prop;
class CompositePrimaryKeysRef extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?int $ref_id = null;
	protected ?int $type_id = null;
}
