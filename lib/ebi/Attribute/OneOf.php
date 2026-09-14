<?php
namespace ebi\Attribute;

/**
 * パラメータ横断の入力検証：列挙したうち「ちょうど1つ」が必須（=1、排他）。
 *
 * 「どれか1つを必ず選ぶ／複数は選べない」制約。実行時、0個（未選択）は RequiredException、
 * 2個以上（複数選択）は InvalidArgumentException（いずれも入力エラー=422）。
 * これは 1 リクエスト内のパラメータに対する入力制約であり、フロー前提を表す #[Requires]
 * （別エンドポイントが生産するトークンの消費）とは全く別物。混同しないこと。
 *
 * OpenAPI parameters には横断制約の表現手段が無いため、operation に x-required-one を出し、
 * body がある場合は JSON Schema の oneOf(required) でも表現する（oneOf＝ちょうど1つ一致）。
 *
 * @example
 *   #[Parameter(name:'id',   type:'int')]
 *   #[Parameter(name:'code', type:'string')]
 *   #[Parameter(name:'email',type:'string')]
 *   #[OneOf(['id', 'code', 'email'])]   // id / code / email のいずれか1つだけ（排他必須）
 *   public function search() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class OneOf{
	/** @param string[] $props 対象パラメータ名（ちょうど1つが必須） */
	public function __construct(
		public array $props,
	){}
}
