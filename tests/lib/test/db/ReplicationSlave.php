<?php
namespace test\db;
/**
 * @readonly
 * @table @['name'=>'replication']
 * @var serial $id
 * @var string $value
 */
class ReplicationSlave extends \ebi\Dao{
	protected $id;
	protected $value;
}
