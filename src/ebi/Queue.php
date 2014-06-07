<?php
namespace ebi;
/**
 * キューの制御
 * @author tokushima
 */
class Queue{
	use \ebi\Plugin;
	
	/**
	 * 挿入
	 * @param string $type
	 * @param string $data
	 * @param integer $priority
	 */
	public static function insert($type,$data,$priority=3){
		$obj = new \ebi\queue\Model();
		$obj->type($type);
		$obj->data($data);
		$obj->priority($priority);

		static::call_class_plugin_funcs('insert',$obj);
	}
	/**
	 * 取得
	 * @param string $type
	 * @param integer $priority
	 */
	public static function get($type,$priority=1){
		$obj = static::call_class_plugin_funcs('get',$type,$priority);
		if(!($obj instanceof \ebi\queue\Model)){
			throw new \ebi\exception\IllegalDataTypeException('must be an of '.get_class($obj));
		}
		return $obj;
	}
	/**
	 * 一覧で取得
	 * @param integer $limit
	 * @param string $type
	 * @param integer $priority
	 */
	public static function gets($limit,$type,$priority=1){
		$result = array();
		while(true){
			try{
				if($limit <= 0) break;
				$result[] = self::get($type,$priority);
				$limit--;
			}catch(\ebi\exception\NotFoundException $e){
				break;
			}
		}
		return $result;
	}
	/**
	 * 削除
	 * @param string $key
	 */	
	public static function delete($key){
		if($key instanceof \ebi\queue\Model) $key = $key->id();
		static::call_class_plugin_funcs('delete',$key);
	}
	/**
	 * 終了とする
	 * @param string $key
	 */	
	public static function finish($key){
		if($key instanceof \ebi\queue\Model) $key = $key->id();
		static::call_class_plugin_funcs('finish',$key);
	}
	/**
	 * 終了していないものをリセットする
	 * @param string $type キューの種類
	 * @param integer $sec
	 * @return ebi.queue.Model[]
	 */	
	public static function reset($type,$sec=86400){
		$time = microtime(true) - (float)$sec;
		return static::call_class_plugin_funcs('reset',$type,$time);
	}
	/**
	 * 一覧を取得する
	 * @param string $type
	 * @param integer $page
	 * @param integer $paginate_by
	 * @param string $order
	 * @param string $pre_order
	 * @return mixed[] ($list,$paginator,$sorter)
	 */
	public static function view($type,$page=1,$paginate_by=30,$order=null,$pre_order=null){
		$paginator = new \ebi\Paginator($paginate_by,$page);
		if(empty($order)) $order = 'id';
		$sorter = \ebi\Sorter::order($order,$pre_order);
		$list = array();
		if(static::has_class_plugin('view')){
			$list = static::call_class_plugin_funcs('view',$type,$paginator,$sorter);
		}
		$paginator->cp(array('type'=>$type,'order'=>$sorter));
		return array($list,$paginator,$sorter);
	}
	/**
	 * 終了したものを削除する
	 * @param string $type キューの種類
	 * @param timestamp $fin 終了時間の秒
	 */
	public static function clean($type,$fin=null,$paginate_by=100){
		if(empty($fin)) $fin = time();
		$paginator = new \ebi\Paginator($paginate_by);
		
		static::call_class_plugin_funcs('clean',$type,$fin,$paginator);
	}
}
