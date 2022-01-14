<?php
namespace test\db;
/**
 * RefFindテーブル, Findテーブルが先に必要
 * @var serial $id
 * @var int $parent_id
 * @var string $value @['cond'=>'parent_id(ref_find.id.parent_id,find.id)','column'=>'value1']
 */
class RefRefFind extends \ebi\Dao{
	protected $id;
	protected $parent_id;
	protected $value;
}
