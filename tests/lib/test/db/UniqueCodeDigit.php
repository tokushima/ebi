<?php
namespace test\db;
use \ebi\Attribute\Prop;
class UniqueCodeDigit extends UniqueCode{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(auto_code_add:true, base:'0123456789')]
	protected ?string $code1 = null;
	#[Prop(auto_code_add:true, base:'0123456789')]
	protected ?string $code2 = null;
	#[Prop(auto_code_add:true, base:'0123456789')]
	protected ?string $code3 = null;

	protected function __verify_code2__(){
		return !preg_match('/^000.+000$/',$this->code2);
	}
}
