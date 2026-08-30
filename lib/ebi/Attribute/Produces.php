<?php
namespace ebi\Attribute;

/**
 * このエンドポイントが成立させる状態トークンを宣言するAttribute（flow token / 効果）
 * トークンの語彙（kind/summary）はこの宣言自体が定義になる（生産箇所が単一の真実の源）。
 * Dt はこれと #[FlowToken]（生産者を持たない外部由来トークン）を集約して x-flow-registry を構築する。
 *
 * @example
 * #[Produces('order.code', via:'response:code', summary:'大口注文コードを発番')]     // 値トークン（後続paramの値になる）
 * #[Produces('order.canceled', via:'effect', when:'success')]                     // 状態トークン（値なし副作用）
 * public function create() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Produces{
	public function __construct(
		/** 確立する状態トークン domain.entity[.qualifier] */
		public string $token,
		/** 値の出所。'response:<name>'（#[Response]名を指す） | 'effect'（値なし副作用） */
		public ?string $via=null,
		/** 効果が成立する条件。'success'(既定) | 'always' | 例外クラス名（分岐を部分表現） */
		public string $when='success',
		public ?string $summary=null,
		/** トークン種別 'value'（値を運ぶ） | 'state'（真偽の副作用）。null なら via から推論（response:*→value / それ以外→state） */
		public ?string $kind=null,
	){}
}
