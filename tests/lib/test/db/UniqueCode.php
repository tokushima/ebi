<?php
namespace test\db;
use \ebi\Attribute\Prop;
class UniqueCode extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(auto_code_add:true)]
	protected ?string $code1 = null;
	#[Prop(auto_code_add:true, max:10)]
	protected ?string $code2 = null;
	#[Prop(auto_code_add:true, max:40)]
	protected ?string $code3 = null;
}
