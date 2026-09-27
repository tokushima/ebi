<?php
namespace ebi\Attribute;

/**
 * クラスを MCP（Model Context Protocol）エンドポイントとして公開する Attribute（クラスに付ける）。
 *
 * `#[Route]` を持つ1クラスが1つのHTTPマウントになるのと対に、`#[McpEndpoint]` を付けた1クラスが
 * 1つのMCPエンドポイントになる。App の automap がこの属性を検出し、そのマウント直下を MCP
 * トランスポートへ振り向ける（そのクラスの継承した #[Route] は automap されない＝HTTPルートは
 * MCP 配下へ漏れない）。公開されるのは `#[McpTool]` を持つメソッドだけ。
 *
 *   #[McpEndpoint]
 *   class MaterialMcp extends \app\Api{}   // Api の #[McpTool] を継承して公開（use 不要）
 *   \ebi\App::run(['mcp' => ['action' => 'app\MaterialMcp']]);   // → POST /mcp
 *
 * ツールの実体を別クラスに委譲したい場合は tools にそのクラス名を渡す（既定は付けたクラス自身）。
 *   #[McpEndpoint(tools: \app\Api::class)]
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class McpEndpoint{
	public function __construct(
		/** #[McpTool] を集めるクラス名。未指定なら属性を付けたクラス自身。 */
		public ?string $tools=null,
	){}
}
