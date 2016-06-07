<?php
namespace test\db;
/**
 * @var serial $id
 * @var string $value
 * @var \test\db\ManyChild[] $children @['cond'=>'id()parent_id']
 */
class ManyParent extends \ebi\Dao{
	protected $id;
	protected $value;
	protected $children;
}
