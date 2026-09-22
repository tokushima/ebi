<?php
namespace test;

use ebi\Attribute\Parameter;
use ebi\Attribute\Response;

class ItemsFixture{
	#[Parameter(name: 'tags', type: 'array', items: 'string')]
	#[Parameter(name: 'ids', type: 'array', items: 'int')]
	#[Parameter(name: 'grid', type: 'array', items: ['int'])]        // int[][]（多段は items 配列で包む）
	#[Parameter(name: 'legacy', type: 'array', items: ['string'])]   // string[][]（同上）
	#[Parameter(name: 'dict', type: 'map', items: 'int')]          // map<string, int>
	#[Parameter(name: 'pages', type: 'map', items: ['int'])]       // map<string, int[]>
	#[Parameter(name: 'plain', type: 'string')]                    // コンテナでない
	#[Response(name: 'names', type: 'array', items: 'string')]
	#[Response(name: 'counts', type: 'array', items: 'int')]
	public function action(): void{
	}
}
