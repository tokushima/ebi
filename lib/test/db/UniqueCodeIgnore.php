<?php
namespace test\db;
/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true,'ctype'=>'digit','max'=>1]
 */
class UniqueCodeIgnore extends \test\db\UniqueCode{
	protected $id;
	protected $code1;
	
	protected function __verify_code1__(){
		return !preg_match('/^[0-8]$/',$this->code1);
	}
}
