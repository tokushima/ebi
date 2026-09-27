<?php
namespace test\mcp;

use \ebi\Attribute\McpEndpoint;
use \ebi\Attribute\McpTool;
use \ebi\Attribute\Parameter;

/**
 * \ebi\McpServer の #[McpTool] 探索・inputSchema生成・ディスパッチを検証するフィクスチャ。
 * #[McpEndpoint] を付けるだけで MCP エンドポイントになる（use 不要）。
 */
#[McpEndpoint]
class Sample extends \ebi\app\Request{
	/**
	 * #[McpTool] に説明を明示。#[Parameter] から inputSchema を生成し、arguments は in_vars で読める。
	 */
	#[McpTool('アイテムを検索して一覧取得する')]
	#[Parameter(name:'query', type:'string', summary:'検索キーワード')]
	#[Parameter(name:'page', type:'int', summary:'ページ番号')]
	public function list_items(): array{
		return [
			'query' => $this->in_vars('query', ''),
			'page' => (int)$this->in_vars('page', 1),
		];
	}

	/**
	 * アイテム詳細を取得する
	 */
	#[McpTool]
	#[Parameter(name:'id', type:'int', require:true, summary:'アイテムID')]
	public function get_item(): array{
		return ['id' => (int)$this->in_vars('id')];
	}

	/**
	 * #[McpTool] が無いので MCP ツールとして公開されない。
	 */
	public function secret(): array{
		return ['secret' => true];
	}
}
