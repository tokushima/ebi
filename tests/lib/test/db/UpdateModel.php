<?php
namespace test\db;
use \ebi\Attribute\Prop;
class UpdateModel extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $value = null;
	protected ?string $abc = null;
	protected ?string $def = null;
	protected ?string $ghi = null;
}
