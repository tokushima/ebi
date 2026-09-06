<?php
namespace ebi\Attribute;

/**
 * プロパティの型と制約を定義するAttribute
 *
 * === モデルプロパティ宣言の規約（新規・改修時はこれに従う） ===
 *
 * 【1】\ebi\Dao は \ebi\Obj を継承し、プロパティ列挙(reflection)・spec 型解決(PHP宣言＞type:＞mixed)の
 *   機構は共通。違いは Dao が永続化のために解釈する "Dao 専用の attribute 値" を持つ点だけ
 *   （serial / primary / auto_now / auto_now_add / auto_code_add / cond / column /
 *    unique / unique_together など）。
 *   運用の目安:
 *   - \ebi\Obj（非永続DTO）: プレーン型は #[VarAttr] 不要。?T だけでよい（spec 型は
 *     SourceAnalyzer が PHP 宣言から補完＝【6】）。#[VarAttr] は type: か option がある時だけ。
 *   - \ebi\Dao: データ列は #[VarAttr] を付ける（空でも良い＝PHP 宣言型を var メタへ載せる。
 *     列の型は var メタ由来。serial/datetime 等の type: や上記 Dao 専用値もここに書く）。
 *
 * 【2】type / nullable は PHP の型宣言から推論される（\ebi\AttributeReader）。
 *   - type 未指定    → ReflectionNamedType::getName()（int/string/float/bool/array）。
 *   - nullable 未指定 → allowsNull()。`?T`=nullable(true,既定は出力されない) /
 *                        `T`(非null)=nullable:false を出力（＝仕様上の非null明示）。
 *   既存 @var（無型）は nullable 既定 true。等価移行では型を足すなら `?T = null` にする
 *   （裸の `T` は nullable:false が付いて非等価になる）。
 *
 * 【3】type: を明示するのは"セマンティック型"だけ ＝ PHP 型で表せない型。
 *   PHP 宣言型は必ず \ebi\T::phpType() に一致させる:
 *     serial / datetime / date / time / intdate → ?int
 *     text / email / alnum                      → ?string
 *     map                                       → array
 *     file（#[Parameter]専用・モデル不使用）     → mixed
 *   プレーン型（int/string/float/bool/array）は type: を書かず PHP 宣言へ一本化する。
 *
 * 【4】string と text は共に PHP string だが検証が違う（\ebi\Validator）:
 *   string は CRLF を除去、text は保持。改行を含み得る本文は必ず type: 'text'。
 *
 * 【5】OpenAPI の description は次の優先で解決される（\ebi\Dt\SourceAnalyzer::property_summary）:
 *     summary:（VarAttr）/ @var の説明   >   プロパティ直前の PHPDoc ブロック(先頭行・@行除去)   >   空
 *   ＝説明は summary: でも PHPDoc ブロックでも書け、どちらも spec に出る（両方あれば summary: 優先）。
 *   ただし // 行コメントは getDocComment の対象外なので出ない。採用は先頭1行のみ。
 *
 * 【6】\ebi\Obj は public も protected も spec に出る（SourceAnalyzer が
 *   isPublic()||(is_obj&&isProtected()) を全リフレクション走査）。型宣言(?T)が無いと spec は
 *   mixed になる → PHP 型宣言を付ければ SourceAnalyzer が拾って正確化（Obj は #[VarAttr] 不要）。
 *
 * @see \ebi\T::phpType()  セマンティック型 → PHP プリミティブ型の対応表
 *
 * @example
 * protected ?string $name = null;                   // Obj: プレーン型は #[VarAttr] 不要（spec型はPHP宣言から）
 *
 * #[VarAttr]                                        // Dao: データ列は #[VarAttr]（空でも型を var メタへ載せる）
 * protected ?int $member_id = null;
 *
 * #[VarAttr(type: 'serial')]                       // セマンティック型は明示＋phpType()に一致
 * protected ?int $id = null;
 *
 * #[VarAttr(type: 'datetime', auto_now_add: true)] // datetime も実体は int
 * protected ?int $create_date = null;
 *
 * #[VarAttr(summary: '保有ポイント')]               // 説明はsummary(=descriptionに出る)
 * protected int $point = 0;                         // 非null＋既定値 → nullable:false
 *
 * #[VarAttr(type: 'text', summary: '本文')]         // 改行保持が要る本文はtext
 * protected ?string $note = null;
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class VarAttr{
	public function __construct(
		public string $type='',
		public ?string $items=null,
		public ?string $summary=null,
		public bool $primary=false,
		public bool $auto_now=false,
		public bool $auto_now_add=false,
		public bool $auto_code_add=false,
		public bool $expose=true,
		public bool $get=true,
		public bool $set=true,
		public bool $unique=false,
		public string|array|null $unique_together=null,
		public bool $require=false,
		public ?bool $nullable=null,
		public int|float|null $min=null,
		public int|float|null $max=null,
		public ?string $cond=null,
		public ?string $column=null,
		public bool $extra=false,
		public ?string $ctype=null,
		public ?string $base=null,
		public ?int $length=null,
		// enum: (推奨) backed enum の FQCN 文字列(EnumClass::class)＝値/ラベルの単一ソース。または [値 => ラベル] 連想(後方互換)。
		public array|string|null $enum=null,
	){}
}
