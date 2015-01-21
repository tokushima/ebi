<?php
namespace ebi\flow\ plugin;
/**
 * Daoを制御するモジュール
 * @author tokushima
 */
class Dao{
	/**
	 * @plugin ebi.Flow
	 * @param \Exception $exception
	 */
	public function flow_exception(\Exception $exception){
		\ebi\Dao::rollback_all();
	}
}