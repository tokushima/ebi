<?php
namespace test\db;
/**
 * @var int $id1 @['primary'=>true]
 * @var int $id2 @['primary'=>true]
 * @var string $value
 */
class DoublePrimary extends \ebi\Dao{
	protected $id1;
	protected $id2;
	protected $value;
}
