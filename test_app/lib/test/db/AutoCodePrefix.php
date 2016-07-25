<?php
namespace test\db;
/**
 * @var string $code @['auto_code_add'=>true,'max'=>16]
 */
class AutoCodePrefix extends \ebi\Dao{
	protected $id;
	protected $code;
	
	public function __unique_code_prefix__($name,$codebase){
		$time = time();

		return \ebi\Code::encode($codebase,date('Y',$time)).
			\ebi\Code::encode($codebase,date('m',$time)).
			\ebi\Code::encode($codebase,date('d',$time)).
			\ebi\Code::encode($codebase,date('H',$time));
	}
}
