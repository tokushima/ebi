<?php
namespace test\db;
use \ebi\Attribute\Prop;
class ChainB extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?int $c_ref = null;
	protected ?string $bval = null;
}
