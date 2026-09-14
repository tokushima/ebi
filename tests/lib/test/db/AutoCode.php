<?php
namespace test\db;
use \ebi\Attribute\Prop;
class AutoCode extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(auto_code_add:true, max:1)]
	protected ?string $code = null;
}
