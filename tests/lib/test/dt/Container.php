<?php
namespace test\dt;
use \ebi\Attribute\Parameter;
use \ebi\Attribute\Response;

class Container extends \ebi\app\Request{
	/**
	 * コンテナ型（配列 / 連想 / 多段 / 混在）のスキーマ生成
	 */
	#[Parameter(name:'tags', type:'array', items:'string')]      // string[]
	#[Parameter(name:'grid', type:'array', items:['int'])]       // int[][]
	#[Parameter(name:'dict', type:'map', items:'int')]           // map<string, int>
	#[Parameter(name:'pages', type:'map', items:['int'])]        // map<string, int[]>
	#[Response(name:'matrix', type:'array', items:['string'])]   // string[][]
	public function shape(): array{
		return ['matrix' => []];
	}
}
