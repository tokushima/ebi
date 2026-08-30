<?php
namespace ebi\Attribute;

/**
 * 生産者（#[Produces]）を持たない flow token の語彙を宣言するAttribute。
 * ユーザ入力/QR/共有リンク/メール等、API の外で成立する ambient トークンの定義に使う。
 * #[Produces] が生産箇所で自らを定義するのと対をなし、Dt はこの2つを集約して
 * x-flow-registry（トークン辞書）を構築する。所有ドメインのクラスに1回ずつ宣言する。
 *
 * @example
 * #[FlowToken('product.serial', kind:'ambient', summary:'製造番号（ユーザ入力/QR、API外で成立）')]
 * class ProductCatalog { ... }
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class FlowToken{
	public function __construct(
		/** 定義するトークン domain.entity[.qualifier] */
		public string $token,
		/** トークン種別 'ambient'（API外で成立=生産者op不要） | 'value' | 'state' */
		public string $kind='ambient',
		public ?string $summary=null,
		/** ambient（生産者op不要）扱いにするか。既定 true */
		public bool $ambient=true,
	){}
}
