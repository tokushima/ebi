<?php
namespace test\flow;

class AutoAction{
	/**
	 * @automap
	 */
	public function index(){
		return ['aaaa'=>'index'];
	}
	
	public function abc(){
		return ['aaaa'=>'abc'];		
	}
	/**
	 * @automap
	 */
	public function def(){
		return ['aaaa'=>'def'];
	}
	/**
	 * @automap
	 */
	public function ghi($a){
		unset($a);
		return ['aaaa'=>'ghi'];
	}
	/**
	 * @automap
	 */
	public function jkl($a,$b,$c=null){
		unset($a,$b,$c);
		return ['aaaa'=>'jkl'];
	}
}