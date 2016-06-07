<?php
namespace test\db;
/**
 * @var serial $id
 * @var integer $parent_id
 * @var \test\db\CrossParent $parent @['cond'=>'parent_id()id']
 */
class CrossChild extends \ebi\Dao{
	protected $id;
	protected $parent_id;
	protected $parent;
}
