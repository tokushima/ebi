<?php
namespace ebi\Attribute;

/**
 * トークンが「在る」こと(FlowRequires)とは別に、トークン属性が「条件を満たす」ことを宣言する述語前提Attribute（gate）。
 * Dt は plan の各段に gate として併記し、宣言の不整合を x-flow-issues に出す（G1=token未定義 / G8=onFail未宣言）。
 * requires（存在）と gate（属性条件）を役割分離する。
 *
 * @example
 * #[FlowGate(token:'product.category', in:['photobook'], bind:'product_code', onFail: InvalidProductException::class, reason:'フォトブック専用')]
 * #[FlowGate(token:'kit.orderable', equals:true, bind:'kit_id', onFail: InvalidProductException::class)]
 * public function create() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class FlowGate{
	public function __construct(
		/** 評価対象の属性トークン（例: product.category / kit.orderable） */
		public string $token,
		/** 満たすべき値集合（いずれかに一致で通過） */
		public ?array $in=null,
		/** 満たすべき単一値（false/0/'' も有効値のため mixed） */
		public mixed $equals=null,
		/** どの入力(#[Parameter]名)が指す対象を評価するか */
		public ?string $bind=null,
		/** 違反時に発火する例外クラス（SomeException::class で渡す。x-throws と突合） */
		public ?string $onFail=null,
		/** 違反理由の説明 */
		public ?string $reason=null,
		/** 評価が有効な条件。'success'(既定) 等。別パラメータ値で対象が変わる場合の条件式にも使う */
		public string $when='success',
		public ?string $summary=null,
	){}
}
