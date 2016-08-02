<?php
namespace test\db;
/**
 * @var string $code @['auto_code_add'=>true,'max'=>32,'ctype'=>'0']
 */
class AutoCodeNumberPrefix extends \ebi\Dao{
	protected $id;
	protected $code;
	
	public function __prefix_code__($codebase){
		$time = time();

		return 'ABC';
	}
}
