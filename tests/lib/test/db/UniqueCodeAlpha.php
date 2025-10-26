<?php
namespace test\db;
/**
 * @var serial $id
 * @var string $code1 @['auto_code_add'=>true,'base'=>'ABCDEFGHIJKLMNOPQRSTUVWXYZ']
 * @var string $code2 @['auto_code_add'=>true,'max'=>10,'base'=>'ABCDEFGHIJKLMNOPQRSTUVWXYZ']
 * @var string $code3 @['auto_code_add'=>true,'max'=>40,'base'=>'ABCDEFGHIJKLMNOPQRSTUVWXYZ']
 */
class UniqueCodeAlpha extends UniqueCode{
	protected $id;
	protected $code1;
	protected $code2;
	protected $code3;
}