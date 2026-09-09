<?php
namespace ebi\Attribute;

/**
 * クラスに付けて、trait / 親クラス由来のプロパティへ「関係マッピング（cond / column）」だけを
 * オーバーレイする Attribute。プロパティを再宣言せずに、そのモデル固有の結合先を宣言できる。
 *
 * #[VarAttr] がプロパティ固有メタ（型・検証）を宣言するのに対し、#[VarBind] は「そのモデルで
 * その値をどこ（どの列・どの結合）から引くか」という束縛だけをクラスレベルで宣言する。
 *
 * 背景: cond / column は関係マッピングで、type / max / require のプロパティ固有メタとは性質が
 * 異なる。VarAttr は TARGET_PROPERTY のため cond だけ変えたい時もプロパティ再宣言が必要になり、
 * 型付き trait との合成非互換（PHP のプロパティ型は再宣言側と一致必須）を招く。束縛だけを分離する。
 *
 * cond の指定は2通り:
 *   - via:  … 別プロパティの結合を流用する（内部で `@{via}` を生成）。参照が自明になる短縮記法。
 *   - cond: … 生の cond DSL。アンカー定義（`col(table.col)`）や `@ref(join)` ハイブリッド用。
 *   （via と cond は排他。両方あれば cond を優先）
 *
 * 解決: \ebi\AttributeReader が var メタを継承順で階層マージする際、各クラス段で当該クラス自身に
 * 付いた #[VarBind] を読み、[prop => ['cond'=>.., 'column'=>..]] として該当プロパティへ上書きする。
 * type / 検証は trait / 親の宣言をそのまま単一ソースとして継承する。
 *
 * @example
 *   #[VarBind(prop: 'name', cond: 'owner_id(owner.id)')]  // アンカー定義（実結合）
 *   #[VarBind(prop: 'zip',  via: 'name')]                 // name の結合を流用（= @name）
 *   class OrderView extends Order{
 *       use AddressProps;   // name / zip は trait 由来のまま（再宣言不要）
 *   }
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class VarBind{
	public function __construct(
		public string $prop,          // 対象プロパティ名（trait / 親由来）
		public ?string $cond=null,    // 生の cond DSL（アンカー定義 / `@ref(join)` ハイブリッド）
		public ?string $via=null,     // 別プロパティの結合を流用（内部で `@{via}`）。cond と排他
		public ?string $column=null,  // プロパティ名と異なる列名
	){}

	/** via を解決した実効 cond を返す。cond 優先、無ければ via→`@{via}`。 */
	public function resolved_cond(): ?string{
		if($this->cond !== null){
			return $this->cond;
		}
		return $this->via !== null ? '@' . $this->via : null;
	}
}
