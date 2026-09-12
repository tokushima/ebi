<?php
namespace ebi\Attribute;

/**
 * result オブジェクト内の「名前付きレスポンスフィールド」を定義する Attribute（OpenAPI responses 相当）
 *
 * @example
 * #[Response(name: 'user', type: \App\Model\User::class)]
 * public function show() {}
 *
 * required/nullable はモデル層スキーマと同一の2軸・同一の既定：
 *   required=true  … result 内にキーが必ず存在する（条件付き省略キーは required:false）
 *   nullable=null  … 未指定は nullable ON 扱い（値が null になり得る）。非nullが確定なら nullable:false
 *
 * ボディ全体が bare 配列 / 単一オブジェクト / バイナリのように result{} ラップに収まらない応答は
 * #[Response] ではなく #[ResponseBody] を使う。両者の併記は不可（Dt 画面のスペック生成時に
 * x-skipped として警告表示される）。
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Response{
	public function __construct(
		public string $name,
		public \ebi\T|string $type=\ebi\T::Mixed,
		public ?string $items=null,
		public ?string $summary=null,
		public bool $deprecated=false,
		public bool $required=true,
		public ?bool $nullable=null,
	){}
}
