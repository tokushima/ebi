<?php
namespace test\dt;

use \ebi\Attribute\Parameter;

/**
 * クラス型コンテナ #[Parameter] を持つ action。app/Request::before → request_validation が
 * request_validate_object / request_validate_container を段数ぶん潜って構造検証する。
 * 検証失敗（必須欠落・段数不一致）は 422 で表出する。
 */
class RowRequest extends \ebi\app\Request{
	#[Parameter(name:'rows', type:'array', items: Row::class)]   // Row[]（depth 1）
	public function submit(){
		return ['ok' => true];
	}

	#[Parameter(name:'grid', type:'array', items: [Row::class])] // Row[][]（depth 2）
	public function grid_submit(){
		return ['ok' => true];
	}
}
