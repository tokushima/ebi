<?php
namespace ebi\queue\plugin;
use \ebi\Q;
/**
 * キューのモジュール
 * @author tokushima
 *
 */
class Dao{
	/**
	 * 挿入
	 * @param \ebi\queue\Model $obj
	 */
	public function insert(\ebi\queue\Model $obj){
		$dao = new \ebi\queue\plugin\Dao\QueueDao();
		$dao->set($obj);
		$dao->save();
		\ebi\queue\plugin\Dao\QueueDao::commit();
	}
	/**
	 * 削除
	 * @param string $id
	 */
	public function delete($id){
		try{
			$obj = \ebi\queue\plugin\Dao\QueueDao::find_get(Q::eq('id',$id));
			$obj->delete();
			\ebi\queue\plugin\Dao\QueueDao::commit();
			return true;
		}catch(\Exception $e){
			return false;
		}
	}
	/**
	 * 終了
	 * @param string $id
	 */
	public function finish($id){
		try{
			$obj = \ebi\queue\plugin\Dao\QueueDao::find_get(Q::eq('id',$id));
			$obj->fin(time());
			$obj->save();
			\ebi\queue\plugin\Dao\QueueDao::commit();
			return true;
		}catch(\Exception $e){
			return false;
		}
	}
	/**
	 * 取得
	 * @param string $type
	 * @param integer $priority
	 */
	public function get($type,$priority){
		while(true){
			try{
				$object = \ebi\queue\plugin\Dao\QueueDao::find_get(
							Q::gte('priority',$priority)
							,Q::eq('type',$type)
							,Q::eq('fin',null)
							,Q::eq('lock',null)
							,Q::order('priority,id')
						);
				$object->lock(microtime(true));
				$object->save(Q::eq('lock',null));
				\ebi\queue\plugin\Dao\QueueDao::commit();
				return $object->get();
			}catch(\ebi\exception\NoRowsAffectedException $e){
			}
		}
	}
	
	/**
	 * リセット
	 * @param string $type
	 * @param integer $priority
	 */
	public function reset($type,$lock_time){
		$result = array();
		foreach(\ebi\queue\plugin\Dao\QueueDao::find(
				Q::eq('fin',null)
				,Q::eq('type',$type)
				,Q::neq('lock',null)
				,Q::lte('lock',$lock_time)
			) as $obj){
			try{
				$obj->lock(null);
				$obj->save(Q::eq('fin',null),Q::eq('id',$obj->id()));
				\ebi\queue\plugin\Dao\QueueDao::commit();
				$result[] = $obj->get();
			}catch(\ebi\exception\NoRowsAffectedException $e){
			}
		}
		return $result;
	}
	/**
	 * 一覧
	 * @param string $type
	 * @param \ebi\Paginator $paginator
	 * @param string $sorter
	 * @return \ebi\queue\Model[]
	 */
	public function view($type,\ebi\Paginator $paginator,$sorter){
		$q = new Q();
		$q->add(Q::eq('fin',null));
		if(!empty($type)) $q->add(Q::eq('type',$type));
		$result = array();
		foreach(\ebi\queue\plugin\Dao\QueueDao::find($q,$paginator,Q::order($sorter)) as $m){
			$result[] = $m->get();
		}
		return $result;
	}
	/**
	 * 終了したものを削除する
	 * @param string $type
	 * @param timestamp $fin
	 */
	public function clean($type,$fin,\ebi\Paginator $paginator){
		foreach(\ebi\queue\plugin\Dao\QueueDao::find(
				Q::eq('type',$type),
				Q::neq('fin',null),
				Q::lte('fin',$fin),
				Q::order('id'),
				$paginator
		) as $obj){
			$obj->delete();
		}
		\ebi\queue\plugin\Dao\QueueDao::commit();
	}
}