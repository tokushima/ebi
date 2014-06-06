<?php
namespace test\db;
/**
 * @var serial $id
 * @var integer $parent_id
 * @var string $value
 */
class ManyChild extends \ebi\Dao{
	protected $id;
	protected $parent_id;
	protected $value;
}