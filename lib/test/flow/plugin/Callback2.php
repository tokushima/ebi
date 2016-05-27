<?php
namespace test\flow\plugin;

class Callback2{
	private $callback;
	private $result = [];
	
	public function before_flow_action_request(\ebi\Request $req){
		if($req->is_vars('callback2')){
			$this->callback = $req->in_vars('callback2');
		}
	}
	
	public function after_flow_action_request(\ebi\Request $req){
		if(!empty($this->callback)){
			$this->result['callback2'] = $this->callback;
			$this->result['result_data2'] = 'ABC';
		}
	}
	
	/**
	 * @context string $callback2 コールバック
	 * @context string $result_data2 結果
	 */
	public function get_after_vars_request(){
		return $this->result;
	}
}