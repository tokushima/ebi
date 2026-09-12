<?php
namespace ebi\Attribute;

/**
 * プロパティの型と制約を定義する Attribute（\ebi\Obj / \ebi\Dao 共通）。
 *
 * 付けるのは type:（セマンティック型）か option が要る時だけ。型/nullable は PHP の型宣言、
 * 説明は PHPDoc に書く（空 / summary だけの #[Prop] は付けない）。option は「Obj/Dao 共通」と
 * 「Dao 専用（DB 列としての定義）」に分かれる ＝ 下の __construct の区分を見る。
 *
 * 型・nullable の解決（\ebi\AttributeReader）:
 *   - type 未指定 → PHP の型宣言に委譲。OpenAPI 型は \ebi\Dt\SourceAnalyzer が、Dao の列型は
 *     命名規約(id→serial / create_date→datetime+auto_now_add 等)が補完する。
 *   - type 明示   → PHP で表せないセマンティック型だけ書く。PHP 宣言型は \ebi\T::phpType() に
 *     一致させる（この一致は不変条件で、テストで機械検査できる）。
 *   - nullable    → `?T`=既定(メタに出さない) / `T`=nullable:false。@var からの移行は `?T = null`。
 *
 * @see \ebi\T::phpType()  セマンティック型 → PHP プリミティブ型の対応表
 * @example
 *   protected ?int $member_id = null;                          // プレーン型は #[Prop] 不要
 *   #[Prop(expose: false)] protected ?int $inner_id = null;    // option が要る時だけ
 *   #[Prop(type: 'text')] protected ?string $note = null;      // 改行を保つ本文（string は CRLF 除去）
 *   #[Prop(type: 'datetime', auto_now_add: true)] protected ?int $create_date = null;
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Prop{
	public function __construct(
		// クラスレベルで付ける時だけ指定：上書き対象のプロパティ名。書いたオプションのキーだけが
		// trait/親のメタに重なる（旧 @var docblock と同じキー単位マージ＝再宣言不要）。プロパティレベルでは null。
		public ?string $name=null,
		// ---- Obj / Dao 共通 ----------------------------------------------------
		// 型（\ebi\Dt\SourceAnalyzer の OpenAPI 型 / \ebi\Validator の検証型）
		public string $type='',
		public ?string $items=null,          // type:'array' の要素型
		public ?bool $nullable=null,         // 未指定は PHP 型宣言の ? から推論
		// 値/ラベルの単一ソースとして backed enum の FQCN(EnumClass::class) 推奨。[値 => ラベル] 連想も可(後方互換)
		public array|string|null $enum=null,

		// 検証（\ebi\Validator）。null=未指定(不出力) / true=必須 / false=明示的に非必須(trait/親のrequire:trueを打ち消す)
		public ?bool $require=null,
		public int|float|null $min=null,     // 数値は値／文字列は文字数
		public int|float|null $max=null,

		// 公開（\ebi\Obj のアクセサ／\ebi\Dt\SourceAnalyzer のドキュメント）
		public ?string $summary=null,
		public ?bool $expose=null,            // false でハッシュ化・ドキュメント出力から除外
		public ?bool $get=null,
		public ?bool $set=null,

		// ---- Dao 専用（\ebi\Dao のみが解釈する DB 列の定義）----------------------
		// キー・制約
		public ?bool $primary=null,
		public ?bool $unique=null,
		public string|array|null $unique_together=null,

		// 列のマッピング
		public ?string $column=null,         // プロパティ名と異なる列名
		public ?string $via=null,            // 別プロパティの結合をそのまま流用し、その結合先テーブルの別列を読む短縮記法（追加ホップ無し）
		// 結合の道筋を構造化して書く。ホップの配列で、先頭のローカル列から順に結合を辿る。各ホップ:
		//   [local, Model::class|'table', target]  … local(現在テーブルの列) = table.target で結合。次ホップの local は table 上とみなす
		//   [local, 'table.target']                … テーブルを文字列で（モデル無しのフォールバック）
		// 先頭ホップの local に `otherprop.col` を置くと、既存の別プロパティ `otherprop` の結合を再利用し、
		// その結合先テーブルの col から続けて結合する（via の再利用を多段へ拡張した形。from だけで完結）。
		// 例: from: [['client_order_id', PrintTicket::class, 'code'], ['delivery_package_id', DeliveryPackage::class, 'id'], ['destination_id', Destination::class, 'id']]
		// 再利用例: from: [['code.book_id', Book::class, 'id']]  … `code` の結合を辿り book_id = book.id
		public ?array $from=null,
		public ?bool $extra=null,            // 列にしない（保存対象外の作業用プロパティ）

		// 自動セット（保存時。命名規約 id / create_date / update_date / code でも補完される）
		public ?bool $auto_now=null,         // 更新の度に現在時刻
		public ?bool $auto_now_add=null,     // 新規作成時に現在時刻
		public ?bool $auto_code_add=null,    // 新規作成時にユニークコード（下の3つで書式を指定）
		public ?string $base=null,           // 使用文字を直接指定
		public ?string $ctype=null,          // base 未指定時の文字種 0:数字 a:小文字 A:大文字 t:token68
		public ?int $length=null,            // 桁数（未指定は max→32）
	){}
}
