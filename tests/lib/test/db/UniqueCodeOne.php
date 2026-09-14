<?php
namespace test\db;
use \ebi\Attribute\Prop;
class UniqueCodeOne extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(auto_code_add:true, max:1, base:'12')]
	protected ?string $code = null;
}
