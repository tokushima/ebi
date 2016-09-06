<?php
namespace test\db;
/**
 * @var string $code @['auto_code_add'=>true,'max'=>16]
 */
class AutoCodePrefix extends \ebi\Dao{
	protected $id;
	protected $code;
	
	public function __prefix_code__($codebase){
		$time = time();

		return \ebi\Code::encode($codebase,date('Y',$time)-1).
			\ebi\Code::encode($codebase,date('m',$time)-1).
			\ebi\Code::encode($codebase,date('d',$time)-1).
			\ebi\Code::encode($codebase,date('H',$time));
	}
}
