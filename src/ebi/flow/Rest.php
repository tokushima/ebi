<?php
namespace ebi\flow;
/**
 * Rest API
 * @author tokushima
 *
 */
class Rest extends \ebi\flow\Request{
	private $model;

	/**
	 * Daoモデルを定義
	 * @param \ebi\Dao $model
	 * @return \ebi\flow\Rest
	 */
	public function model(\ebi\Dao $model){
		$this->model = $model;
		return $this;
	}
	/**
	 * 追加
	 */
	protected function create(){
		if(empty($this->model)){
			\ebi\HttpHeader::send_status(405);
			exit;
		}
		if($this->is_post()){
			foreach($this->model->props(false) as $k => $v){
				if($this->is_vars($k)){
					$this->model->{$k}($this->in_vars($k));
				}
			}
			$this->model->save();
		}
		return;
	}
	/**
	 * 取得
	 * @param mixed $id
	 */
	protected function show($id){
		if(empty($this->model)){
			\ebi\HttpHeader::send_status(405);
			exit;
		}
		$class = get_class($this->model);
		$model = $class::find_get(\ebi\Q::eq($this->primary(),$id));
		
		return $model->props();
	}
	/**
	 * 削除
	 * @param mixed $id
	 */
	protected function destroy($id){
		if(empty($this->model)){
			\ebi\HttpHeader::send_status(405);
			exit;
		}
		$class = get_class($this->model);
		$model = $class::find_get(\ebi\Q::eq($this->primary(),$id));		
		
		$model->delete();
		return;
	}
	/**
	 * 更新
	 * @param mixed $id
	 */
	protected function update($id){
		if(empty($this->model)){
			\ebi\HttpHeader::send_status(405);
			exit;
		}
		$class = get_class($this->model);
		$model = $class::find_get(\ebi\Q::eq($this->primary(),$id));		
		
		foreach($model->props(false) as $k => $v){
			if($this->is_vars($k)){
				$model->{$k}($this->in_vars($k));
			}
		}
		$model->save();
		return;
	}
	
	private function primary(){
		$primarys = [];
		foreach($this->model->primary_columns() as $primary){
			$primarys[] = $primary->name();
		}
		if(sizeof($primarys) != 1){
			throw new \InvalidArgumentException();
		}
		return $primarys[0];
	}
	/**
	 * HTTPメソッドでのルーティング
	 * @param mixed $id
	 */
	public function resources($id=null){
		if($this->is_post()){
			return $this->create();
		}else if($this->is_put()){
			return $this->update($id);
		}else if($this->is_delete()){
			return $this->destroy($id);
		}
		return $this->show($id);
	}
}