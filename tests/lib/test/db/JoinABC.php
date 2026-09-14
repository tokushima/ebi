<?php
namespace test\db;
use \ebi\Attribute\Prop;
use \ebi\Attribute\Table;
/**
 * JoinA, JoinB, JoinCテーブルが先に必要
 */
#[Table(name:'join_a')]
class JoinABC extends \ebi\Dao{
	#[Prop(type:'serial')]
	protected ?int $id = null;
	#[Prop(from: [['id', JoinC::class, 'a_id'], ['b_id', JoinB::class, 'id']], column:'name')]
	protected ?string $name = null;
}
