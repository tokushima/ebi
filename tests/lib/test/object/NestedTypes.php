<?php
namespace test\object;

use \ebi\Attribute\Prop;

/**
 * 多段コンテナ型の Obj。#[Prop] のサフィックス表記が正準形（type + attr 種別列）へ畳まれ、
 * \ebi\Obj::___set___ が外1段を反復・残りを \ebi\Validator へ委譲してハイドレートすることの確認用。
 */
class NestedTypes extends \ebi\Obj{
	#[Prop(type:'array', items:['int'])]
	protected $grid;   // int[][]（多段は items 配列で表す）

	#[Prop(type:'map', items:['int'])]
	protected $pages;  // map<string, int[]>
}
