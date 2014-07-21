<?php
namespace test\db;
/**
 * JoinA, JoinB, JoinCテーブルが先に必要
 * @table @['name'=>'join_a']
 * @var serial $id
 * @var string $name @['column'=>'name','cond'=>'id(join_c.a_id.b_id,join_b.id)']
 */
class JoinABC extends \ebi\Dao{
	protected $id;
	protected $name;
}
