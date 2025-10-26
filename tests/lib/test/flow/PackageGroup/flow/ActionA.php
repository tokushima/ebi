<?php
namespace test\flow\PackageGroup\flow;

class ActionA extends \ebi\flow\Request{
	/**
	 * @automap
	 */
	public function abc(){
		
	}
	
	/**
	 * @automap
	 */
	public function def(){
		
	}
	
	/**
	 * エラーになる
	 * @automap
	 */
	public function ghi(){
		throw new \Exception('エラー');
	}
	
	/**
	 * @automap
	 */
	public function jkl(){
		
	}
}
