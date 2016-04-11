<?php
namespace test\db;
/**
 * 計算
 * @var serial $id
 * @var integer $price
 * @var string $type
 * @var string $name
 * @var number $float
 * 
 * @var \test\db\Crud $crud @['extra'=>true]
 */
class Calc extends \ebi\Dao{
	protected $id;
	protected $price;
	protected $type;
	protected $name;
	protected $float;
	
	protected $crud;
}
