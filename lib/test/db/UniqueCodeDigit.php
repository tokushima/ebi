<?php
namespace test\db;
/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true,'ctype'=>'digit']
 * @var string $code2 @['auto_code_add'=>true,'max'=>10,'ctype'=>'digit']
 * @var string $code3 @['auto_code_add'=>true,'max'=>40,'ctype'=>'digit']
 */
class UniqueCodeDigit extends UniqueCode{
	protected $id;
	protected $code1;
	protected $code2;
	protected $code3;
	
	protected function __verify_code2__(){
		return !preg_match('/^000.+000$/',$this->code2);
	}
}
