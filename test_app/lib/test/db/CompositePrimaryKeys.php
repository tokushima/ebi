<?php
namespace test\db;
/**
 * @var integer $id1 @['primary'=>true]
 * @var integer $id2 @['primary'=>true]
 * @var string $value
 */
class CompositePrimaryKeys extends \ebi\Dao{
	protected $id1;
	protected $id2;
	protected $value;
}
