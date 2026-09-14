<?php
namespace test\db;
use \ebi\Attribute\Prop;
class UniqueCodeAlpha extends UniqueCode{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(auto_code_add:true, base:'ABCDEFGHIJKLMNOPQRSTUVWXYZ')]
	protected ?string $code1 = null;
	#[Prop(auto_code_add:true, max:10, base:'ABCDEFGHIJKLMNOPQRSTUVWXYZ')]
	protected ?string $code2 = null;
	#[Prop(auto_code_add:true, max:40, base:'ABCDEFGHIJKLMNOPQRSTUVWXYZ')]
	protected ?string $code3 = null;
}