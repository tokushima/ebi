<?php
namespace test\flow;

class Sample{
	public function after_redirect(){
		return ['next_var_A'=>'ABC','next_var_B'=>'DEF'];
	}
	public function after_to($a=null,$b=null){
		return ['after_to_a'=>$a,'after_to_b'=>$b];
	}
	/**
	 * @context integer $id
	 * @context \test\db\Find[] $model_list
	 */
	public function model_list(){
		return [
			'model_list'=>\test\db\Find::find_all(),
			'id'=>10,
		];
	}
}
