<?php
namespace test\db;
/**
 *  RefFindテーブルが先に必要
 * @table @['name'=>'ref_find']
 * @var serial $id
 * @var int $parent_id
 * @var \test\db\Find $parent @['cond'=>'parent_id()id']
 */
class HasFind extends \ebi\Dao{
	protected $id;
	protected $parent_id;
	protected $parent;
}
