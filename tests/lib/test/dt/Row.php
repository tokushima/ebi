<?php
namespace test\dt;

use \ebi\Attribute\Prop;

/**
 * app/Request のクラス型コンテナ構造検証（request_validate_object / container）の要素クラス。
 * n は必須。
 */
class Row extends \ebi\Obj{
	#[Prop(type:'int', require:true)]
	protected $n;

	#[Prop(type:'string')]
	protected $label;
}
