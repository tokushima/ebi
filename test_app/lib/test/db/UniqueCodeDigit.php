<?php
namespace test\db;
/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true,'base'=>'0123456789']
 * @var string $code2 @['auto_code_add'=>true,'base'=>'0123456789']
 * @var string $code3 @['auto_code_add'=>true,'base'=>'0123456789']
 */
class UniqueCodeDigit extends UniqueCode{
	protected $id;
	protected $code1;
	protected $code2;
	protected $code3;
	
	protected function __verify_props__($prop_name){
		if($prop_name == 'code2'){
			return !preg_match('/^000.+000$/',$this->code2);
		}
	}
}
