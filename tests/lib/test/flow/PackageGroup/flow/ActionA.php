<?php
namespace test\flow\PackageGroup\flow;

class ActionA extends \ebi\flow\Request{
	/**
	 * とてもとても長いサマリーがあった場合は折り返されて表示されることを確認するためのテストケース
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
