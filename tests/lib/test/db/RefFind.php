<?php
namespace test\db;
/**
 * Findが先に必要
 * @var serial $id
 * @var int $parent_id
 * @var string $value @['cond'=>'parent_id(find.id)','column'=>'value1']
 * @var string $value2 @['cond'=>'@value']
 */
class RefFind extends \ebi\Dao{
	protected $id;
	protected $parent_id;
	protected $value;
	protected $value2;
	
	private $private_value;
}
