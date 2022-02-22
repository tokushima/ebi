<?php
namespace test\db;
/**
 * @var serial $id
 * @var int $u1 @['unique_together'=>['u2','u3']]
 * @var int $u2
 * @var int $u3
 */
class UniqueTripleVerify extends \ebi\Dao{
	protected $id;
	protected $u1;
	protected $u2;
	protected $u3;
}
