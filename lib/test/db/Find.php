<?php
namespace test\db;
/**
 * @var serial $id @['hash'=>false]
 * @var number $order
 * @var timestamp $updated
 * @var string $value1
 * @var string $value2
 * 
 * @var test.db.Calc $calc @['extra'=>true]
 */
class Find extends \ebi\Dao{
	protected $id;
	protected $order;
	protected $value1;
	protected $value2;
	protected $updated;
	
	protected $calc;
}
