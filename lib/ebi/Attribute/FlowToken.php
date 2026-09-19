<?php
namespace ebi\Attribute;

/**
 * 生産者（#[FlowProduces]）を持たない flow token の語彙を宣言するAttribute。
 * ユーザ入力/QR/共有リンク/メール等、API の外で成立する ambient トークンの定義に使う。
 * #[FlowProduces] が生産箇所で自らを定義するのと対をなし、Dt はこの2つを集約して
 * x-flow-registry（トークン辞書）を構築する。所有ドメインのクラスに1回ずつ宣言する。
 *
 * @example
 * #[FlowToken('product.serial', kind:'ambient', summary:'製造番号（ユーザ入力/QR、API外で成立）')]
 * #[FlowToken('session.user', kind:'ambient', reason:'session', summary:'ログインセッション（アプリ内ログイン系で確立）')]
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
		/**
		 * ambient トークンの成立元。Dt が inputs[].reason に反映する。
		 *   'external' … 系外/out-of-band（メール/PIN/QR/共有リンク等、ユーザー操作待ち。establisher 無しが正常）
		 *   'session'  … アプリ内 op で張れる。#[FlowProduces(..., ambient:true)] の producer 集合が establishedBy になる
		 *   null       … Dt が establisher の有無から導出（1件以上→session / 0件→external 扱い、ただし Lint 警告対象）
		 */
		public ?string $reason=null,
	){}
}
