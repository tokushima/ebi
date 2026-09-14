<?php
namespace test\db;
use \ebi\Attribute\Prop;
use \ebi\Attribute\Table;
#[Table(name:'abc', create:false)]
class AbcNoCreate extends \ebi\Dao{
	#[Prop(type:'serial', expose:false)]
	protected ?int $id = null;
	protected ?string $value = null;
}
