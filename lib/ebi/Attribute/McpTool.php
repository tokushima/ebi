<?php
namespace ebi\Attribute;

/**
 * メソッドを MCP ツールとして公開する Attribute。
 *
 * `#[Route]` が HTTP エンドポイントを定義するのと対になり、`#[McpTool]` は同じメソッドを
 * MCP（Model Context Protocol）のツールとして公開する。ツールの inputSchema は
 * `#[Parameter]` から OpenAPI と同じ機構で自動生成され、実行時の入力は既存の
 * `in_vars()` でそのまま読める。
 *
 * @example
 * #[McpTool('アイテムを検索して一覧取得する')]
 * #[Parameter(name: 'query', type: 'string', summary: '検索キーワード')]
 * public function list_items(): array { ... }
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class McpTool{
	public function __construct(
		/** ツールの説明。未指定なら docコメントの先頭行（summary）を使う。第1引数（例: #[McpTool('説明')]）。 */
		public ?string $description=null,
		/** ツール名。未指定ならメソッド名を使う（例: #[McpTool(name:'custom')]）。 */
		public ?string $name=null,
	){}
}
