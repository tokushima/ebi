<?php
namespace ebi\Attribute;

/**
 * このエンドポイントが成立させる状態トークンを宣言するAttribute（flow token / 効果）
 * トークンの語彙（kind/summary）はこの宣言自体が定義になる（生産箇所が単一の真実の源）。
 * Dt はこれと #[FlowToken]（生産者を持たない外部由来トークン）を集約して x-flow-registry を構築する。
 *
 * @example
 * #[FlowProduces('order.code', via:'response:code', summary:'大口注文コードを発番')]     // 値トークン（後続paramの値になる）
 * #[FlowProduces('order.canceled', via:'effect', when:'success')]                     // 状態トークン（値なし副作用）
 * #[FlowProduces('session.user', via:'effect', kind:'state', ambient:true, summary:'セッション確立')] // plan非表示・establishedByにのみ出す
 * public function create() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class FlowProduces{
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
		/**
		 * ambient 確立: この op はトークンを「張る」が plan には段として出さず、
		 * requires 側の inputs[].establishedBy にのみ現れる。login/auth_* 等のセッション確立に使う。
		 * 対象トークンは #[FlowToken(kind:'ambient', reason:'session')] で定義済みであること。
		 * 既定 false（通常の produces=plan に段として現れる）。
		 */
		public bool $ambient=false,
	){}
}
