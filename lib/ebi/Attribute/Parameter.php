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
 * #[Parameter(name: 'pages', type: 'map', items: [Block::class])]    // map<string, Block[]>（配列で包むと1段深くなる）
 * #[Parameter(name: 'grid', type: 'array', items: ['int'])]          // int[][]
 * #[Parameter(name: 'file', type: 'string', format: 'binary', require: true)] // ファイルアップロード（multipart/form-data）
 * #[Parameter(name: 'title', type: 'string', max: 255)]                                // 文字数上限（実行時検証＋OpenAPI maxLength）
 * #[Parameter(name: 'code', type: 'string', pattern: '^[0-9]{6}$', example: '123456')] // 形式ヒント（OpenAPI pattern・spec専用/実行時検証なし）
 * public function create() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Parameter{
	public function __construct(
		public string $name,
		// スカラ型は 'int' 等の正準文字列（\ebi\T::Int でも可）、クラス型は \Foo\Bar::class。
		public \ebi\T|string $type=\ebi\T::String,
		// type:'array'/'map' の要素型。配列で包むと1段深いコンテナ（[X::class] = X[]、[[X::class]] = X[][]）。'X[]' 文字列表記も可。
		public \ebi\T|string|array|null $items=null,
		public ?string $summary=null,
		public bool $require=false,
		// min/max は【実行時強制＋OpenAPI出力】の二役（長さ/値域の単一ソース）。
		//   実行時: request_validation → Validator::value が読み、string/text/alnum/intdate は文字数(mb_strlen)、数値型は値域として検証（未充足は LengthException）。
		//   OpenAPI: スキーマ型で出し分け＝string系→minLength/maxLength、integer/number系→minimum/maximum。
		public int|float|null $min=null,
		public int|float|null $max=null,
		// pattern/example は【OpenAPI(spec/MCP)出力専用・実行時検証しない】。ebi に実行時の正規表現検証は無い
		//   （長さ/値域を実行時に強制したいなら上記 min/max を使う。pattern はドキュメント上の形式ヒント）。
		// 値の正規表現制約（OpenAPI: pattern）。ECMA262 互換の正規表現文字列（例: '^[0-9]{6}$'）。
		public ?string $pattern=null,
		// 例値（OpenAPI 3.1 / JSON Schema: examples 配列として出力）。単一値を渡す。
		public mixed $example=null,
		public ?string $format=null,
		public bool $deprecated=false,
		// enum: (推奨) backed enum の FQCN 文字列(EnumClass::class)＝値/ラベルの単一ソース。または [値 => ラベル] 連想(後方互換)。
		public array|string|null $enum=null,
		// enum_subset: enum が enum クラス参照のとき、部分集合を返す static メソッド名（リクエストの一部許容）。
		public ?string $enum_subset=null,
	){}
}
