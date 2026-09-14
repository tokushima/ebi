<?php
namespace test\db;
use \ebi\Attribute\Prop;
class AutoCodeNumberPrefix extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(auto_code_add:true, max:32, ctype:'0')]
	protected ?string $code = null;

	public function __prefix_code__($codebase){
		$time = time();

		return 'ABC';
	}
}
