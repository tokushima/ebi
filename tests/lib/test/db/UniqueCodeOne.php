<?php
namespace test\db;
/**
 * @var serial $id
 * @var string $code @['auto_code_add'=>true,'max'=>1,'base'=>'12']
 */
class UniqueCodeOne extends \ebi\Dao{
	protected $id;
	protected $code;
}
