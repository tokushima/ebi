<?php
namespace ebi\queue\plugin\Dao;
/**
 * キュー用のDBモデル
 * @var serial $id
 * @var number $lock
 * @var string $type @['max'=>32,'require'=>true]
 * @var string $data @['max'=>255]
 * @var timestamp $fin
 * @var integer $priority
 * @var timestamp $create_date @['auto_now_add'=>true]
 * @author tokushima
 */
class QueueDao extends \ebi\Dao{
	protected $id;
	protected $type;
	protected $data;
	protected $lock;
	protected $fin;
	protected $priority;
	protected $create_date;
	
	public function set(\ebi\queue\Model $obj){
		foreach($obj->props() as $k => $v) $this->{$k} = $v;
		return $this;
	}
	public function get(){
		$obj = new \ebi\queue\Model();
		foreach($this->props() as $k => $v) $obj->{$k}($v);
		return $obj;
	}
}