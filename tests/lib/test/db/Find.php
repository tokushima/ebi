<?php
namespace test\db;
use \ebi\Attribute\Prop;
class Find extends \ebi\Dao{
	#[Prop(type:'serial', expose:false)]
	protected ?int $id = null;
	protected ?int $order = null;
	protected ?string $value1 = null;
	protected ?string $value2 = null;
	#[Prop(type:'datetime')]
	protected ?int $updated = null;
}
