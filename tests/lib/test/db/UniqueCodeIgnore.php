<?php
namespace test\db;
use \ebi\Attribute\Prop;
class UniqueCodeIgnore extends \test\db\UniqueCode{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(auto_code_add:true, base:'0123456789', max:1)]
	protected ?string $code1 = null;

	protected function __verify_code1__(){
		return !preg_match('/^[0-8]$/',$this->code1);
	}
}
