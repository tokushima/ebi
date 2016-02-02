<?php
namespace test\flow\plugin;

class AddVars{
	public function get_after_vars_request(){
		return ['add1'=>'AAA','add2'=>'BBB'];
	}
}