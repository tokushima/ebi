<?php
namespace test\flow;

class Sample{
	public function after_redirect(){
		return ['next_var_A'=>'ABC','next_var_B'=>'DEF'];
	}
	public function after_to($a=null,$b=null){
		return ['after_to_a'=>$a,'after_to_b'=>$b];
	}
}
