<?php
namespace test\db;
/**
 * JoinA, JoinB, JoinCテーブルが先に必要
 * @table @['name'=>'join_a']
 * @var serial $id
 * @var integer $c_id @['column'=>'id','cond'=>'id(join_c.a_id)']
 * @var integer $b_id @['column'=>'id','cond'=>'@c_id.b_id(join_b.id)']
 * 
 * @var string $name @['cond'=>'@b_id']
 * @var integer $b_id2 @['column'=>'id','cond'=>'@c_id.b_id(join_b.id)']
 */
class JoinABBCC extends \ebi\Dao{
	protected $id;
	protected $b_id2;
	protected $b_id;
	protected $c_id;
	protected $name;
}
