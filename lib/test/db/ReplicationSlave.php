<?php
namespace test\db;
/**
 * @table['name'=>'replication','update'=>false,'create'=>false,'delete'=>false]
 * @var serial $id
 * @var string $value
 */
class ReplicationSlave extends \ebi\Dao{
	protected $id;
	protected $value;
}
