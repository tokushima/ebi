<?php
namespace test\db;
/**
 * @var serial $id
 * @var int $u1 @['unique_together'=>'u2']
 * @var int $u2
 */
class UniqueVerify extends \ebi\Dao{
	protected $id;
	protected $u1;
	protected $u2;
}