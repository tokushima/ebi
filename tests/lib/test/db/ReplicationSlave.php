<?php
namespace test\db;
use \ebi\Attribute\Prop;
use \ebi\Attribute\Table;
use \ebi\Attribute\ReadonlyModel;
#[ReadonlyModel]
#[Table(name:'replication')]
class ReplicationSlave extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	protected ?string $value = null;
}
