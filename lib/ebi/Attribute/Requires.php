<?php
namespace ebi\Attribute;

/**
 * このエンドポイントを呼ぶ前に成立していなければならない状態トークンを宣言するAttribute（flow token / 前提）
 * registry に登録済みのトークンのみ有効（Dtが照合する）。
 *
 * @example
 * #[Requires('order.code', bind:'code')]                    // 値トークン: この前提が #[Parameter(name:'code')] の値を供給
 * #[Requires('product.serial', bind:'serial_no')]           // ambient（ユーザ入力/QR等、API外で成立）
 * #[Requires('payment.authorized', optional:true)]          // soft: 必須ではないが順序ヒント
 * public function detail() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Requires{
	public function __construct(
		/** 前提トークン（registryに存在必須） domain.entity[.qualifier] */
		public string $token,
		/**
		 * この前提が値を供給する #[Parameter] 名（値トークン時）。
		 * location プレフィックスも可: "header:Authorization" / "cookie:X" / "query:X" / "path:X" / "body:X"。
		 * header/cookie は Bearer 等 security scheme 由来を許容する（例: トークンログインの member.auth_token）。
		 */
		public ?string $bind=null,
		/** false=hard(必須) / true=soft(推奨・順序ヒントのみ) */
		public bool $optional=false,
		public ?string $summary=null,
	){}
}
