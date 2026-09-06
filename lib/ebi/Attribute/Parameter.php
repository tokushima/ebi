<?php
namespace ebi\Attribute;

/**
 * リクエストパラメータを定義するAttribute（OpenAPI parameters相当）
 *
 * @example
 * #[Parameter(name: 'email', type: 'string', require: true)]
 * #[Parameter(name: 'age', type: 'int', min: 0, max: 150)]
 * #[Parameter(name: 'tags', type: 'array', items: 'string')]
 * #[Parameter(name: 'pages', type: 'map', items: 'mixed')]        // map<string, mixed>（OpenAPI: additionalProperties）
 * #[Parameter(name: 'sections', type: 'map', items: Section::class)] // map<string, Section>
 * #[Parameter(name: 'file', type: 'string', format: 'binary', require: true)] // ファイルアップロード（multipart/form-data）
 * public function create() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Parameter{
	public function __construct(
		public string $name,
		// スカラ型は 'int' 等の正準文字列（\ebi\T::Int でも可）、クラス型は \Foo\Bar::class。
		public \ebi\T|string $type=\ebi\T::String,
		public ?string $items=null,
		public ?string $summary=null,
		public bool $require=false,
		public int|float|null $min=null,
		public int|float|null $max=null,
		public ?string $format=null,
		public bool $deprecated=false,
		// enum: (推奨) backed enum の FQCN 文字列(EnumClass::class)＝値/ラベルの単一ソース。または [値 => ラベル] 連想(後方互換)。
		public array|string|null $enum=null,
		// enum_subset: enum が enum クラス参照のとき、部分集合を返す static メソッド名（リクエストの一部許容）。
		public ?string $enum_subset=null,
	){}
}
