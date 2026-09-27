<?php
namespace test\mcp;

use \ebi\Attribute\McpEndpoint;

/**
 * 既存アクション(LeakApi)を継承して MCP 化するパターンのフィクスチャ。
 * extends で LeakApi の #[McpTool] を継承しつつ、#[McpEndpoint] を付けるだけでエンドポイント化する（use 不要）。
 */
#[McpEndpoint]
class LeakEndpoint extends LeakApi{
}
