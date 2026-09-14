<?php
namespace test\db;
use \ebi\Attribute\Prop;
class Paginator extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?int $order = null;
	#[Prop(auto_code_add:true, ctype:'0')]
	protected ?string $code1 = null;
	#[Prop(auto_code_add:true, ctype:'a')]
	protected ?string $code2 = null;
	#[Prop(type:'datetime', auto_now:true)]
	protected ?int $updated = null;
}
