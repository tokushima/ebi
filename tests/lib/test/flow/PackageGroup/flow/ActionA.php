<?php
namespace test\flow\PackageGroup\flow;

use \ebi\Attribute\Route;

class ActionA extends \ebi\flow\Request{
	/**
	 * とてもとても長いサマリーがあった場合は折り返されて表示されることを確認するためのテストケース
	 */
	#[Route]
	public function abc(){

	}

	#[Route]
	public function def(){

	}

	/**
	 * エラーになる
	 */
	#[Route]
	public function ghi(){
		throw new \Exception('エラー');
	}

	#[Route]
	public function jkl(){

	}
}
