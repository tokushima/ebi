<?php
namespace test\db;
use \ebi\Attribute\Prop;
class ChainC extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $cval = null;
}
