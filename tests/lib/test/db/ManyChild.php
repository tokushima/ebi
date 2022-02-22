<?php
namespace test\db;
/**
 * @var serial $id
 * @var int $parent_id
 * @var string $value
 */
class ManyChild extends \ebi\Dao{
	protected $id;
	protected $parent_id;
	protected $value;
}