<?php
namespace test\mcp;

use \ebi\Attribute\Route;
use \ebi\Attribute\McpTool;
use \ebi\Attribute\Parameter;

/**
 * HTTP ルート(#[Route]) と MCP ツール(#[McpTool]) を併せ持つ既存アクション想定のフィクスチャ。
 * これを継承した Endpoint が、HTTP ルートを /mcp 配下へ漏らさない（automap しない）ことを検証する。
 */
class LeakApi extends \ebi\app\Request{
	/** HTTP 専用（MCPツールにしない書き込み想定） */
	#[Route]
	public function writer(): array{
		return ['written' => true];
	}

	/** MCP ツール兼 HTTP */
	#[Route]
	#[McpTool('読み取り')]
	#[Parameter(name:'id', type:'int', require:true, summary:'ID')]
	public function reader(): array{
		return ['id' => (int)$this->in_vars('id')];
	}
}
