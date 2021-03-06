<?php
namespace test\flow\plugin;

class Callback{
	private $callback;
	private $result = [];
	
	public function before_flow_action_request(\ebi\Request $req){
		if($req->is_vars('callback')){
			$this->callback = $req->in_vars('callback');
		}
	}
	
	public function after_flow_action_request(\ebi\Request $req){
		if(!empty($this->callback)){
			$this->result['callback'] = $this->callback;
			$this->result['result_data'] = 'XYZ';
		}
	}
	
	/**
	 * @context string $callback コールバック
	 * @context string $result_data 結果
	 */
	public function get_after_vars_request(){
		return $this->result;
	}
}