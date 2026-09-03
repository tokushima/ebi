<?php
namespace ebi\Attribute;

/**
 * データ依存（Requires/Produces）では表せないUX上の順序だけを補助的に宣言するAttribute（flow token / 順序ヒント）
 * endpoint は MCP の operationId を指す（Dtが在庫と照合する）。
 * 自分視点の命名: 「このメソッドは endpoint に"続く"（endpoint が先）」。
 *
 * @example
 * #[Follows('bulkorder_estimate', soft:true)]   // 見積りの後に呼ぶ想定（データ依存は無いが順序として）
 * public function create() {}
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Follows{
	public function __construct(
		/** 先行して呼ばれる想定のエンドポイント（MCPのoperationId） */
		public string $endpoint,
		/** true=soft(推奨) / false=hard(強い順序) */
		public bool $soft=true,
		public ?string $summary=null,
	){}
}
