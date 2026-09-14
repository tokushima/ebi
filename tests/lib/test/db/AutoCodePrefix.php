<?php
namespace test\db;
use \ebi\Attribute\Prop;
class AutoCodePrefix extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(auto_code_add:true, max:16)]
	protected ?string $code = null;

	public function __prefix_code__($codebase){
		$time = time();

		return \ebi\Code::encode($codebase,date('Y',$time)-1).
			\ebi\Code::encode($codebase,date('m',$time)-1).
			\ebi\Code::encode($codebase,date('d',$time)-1).
			\ebi\Code::encode($codebase,date('H',$time));
	}
}
