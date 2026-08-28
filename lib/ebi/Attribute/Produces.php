<?php
namespace ebi\Attribute;

/**
 * このエンドポイントが成立させる状態トークンを宣言するAttribute（flow token / 効果）
 * registry に登録済みのトークンのみ有効（Dtが照合する）。
 *
 * @example
 * #[Produces('order.code', via:'response:code', summary:'大口注文コードを発番')]     // 値トークン（後続paramの値になる）
 * #[Produces('order.canceled', via:'effect', when:'success')]                     // 状態トークン（値なし副作用）
 * public function create() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Produces{
	public function __construct(
		/** 確立する状態トークン（registryに存在必須） domain.entity[.qualifier] */
		public string $token,
		/** 値の出所。'response:<name>'（#[Response]名を指す） | 'effect'（値なし副作用） */
		public ?string $via=null,
		/** 効果が成立する条件。'success'(既定) | 'always' | 例外クラス名（分岐を部分表現） */
		public string $when='success',
		public ?string $summary=null,
	){}
}
