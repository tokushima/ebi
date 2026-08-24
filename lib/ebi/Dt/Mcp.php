<?php
namespace ebi\Dt;

/**
 * dt が生成する OpenAPI をもとに、アプリの API ドキュメントを MCP から検索・参照できるようにする。
 *
 * これは「API を実行する」ものではなく「API ドキュメントを引く」ためのツールを提供する（読み取り専用）。
 * 第三者製ブリッジ（npx 等）を介さず ebi 自身が MCP(JSON-RPC 2.0 / Streamable HTTP) を喋る。
 *
 * 公開ツール:
 *  - search_endpoints … キーワードでエンドポイントを検索（path/summary/description/tag/operationId 対象）
 *  - get_endpoint     … operationId でエンドポイントの詳細（parameters/requestBody/responses/参照スキーマ）を取得
 *  - list_tags        … タグ一覧
 *  - get_schema       … components schema を名前で取得
 *
 * envelope（レスポンス形式）は App と同じ Accept ヘッダ判定（\ebi\App::is_envelope()）に従う。
 */
class Mcp{
	// 対応するMCPプロトコル版（新しい順）。
	// 使う機能は tools/list・tools/call の基本サブセットのみで、これらのリビジョン間で安定しているため複数を対応とする。
	private const PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];
	private const PROTOCOL_VERSION = self::PROTOCOL_VERSIONS[0]; // 既定（最新）
	private const SERVER_NAME = 'endpoints-mcp';

	private string $entry;
	private bool $envelope;
	private ?array $spec = null;

	public function __construct(string $entry, bool $envelope=false){
		$this->entry = $entry;
		$this->envelope = $envelope;
	}

	/**
	 * JSON-RPC 2.0 リクエストを処理して応答配列を返す。
	 * 通知(idなし)には null を返す（応答不要）。
	 */
	public function handle(array $req): ?array{
		$id = $req['id'] ?? null;
		$method = $req['method'] ?? '';
		$params = $req['params'] ?? [];

		try{
			switch($method){
				case 'initialize':
					// バージョンネゴシエーション: クライアント要求版を対応集合に含めばエコー、無ければ既定版を返す
					$requested = $params['protocolVersion'] ?? null;
					$version = (is_string($requested) && in_array($requested, self::PROTOCOL_VERSIONS, true))
						? $requested
						: self::PROTOCOL_VERSION;
					return $this->result($id, [
						'protocolVersion' => $version,
						'capabilities' => ['tools' => ['listChanged' => false]],
						'serverInfo' => ['name' => self::SERVER_NAME, 'version' => $this->server_version()],
					]);

				case 'notifications/initialized':
				case 'notifications/cancelled':
					return null;

				case 'ping':
					return $this->result($id, new \stdClass());

				case 'tools/list':
					return $this->result($id, ['tools' => $this->tool_defs()]);

				case 'tools/call':
					$name = $params['name'] ?? '';
					$arguments = $params['arguments'] ?? [];
					return $this->result($id, $this->call_tool($name, is_array($arguments) ? $arguments : []));

				default:
					if($id === null){
						return null;
					}
					return $this->error($id, -32601, 'Method not found: '.$method);
			}
		}catch(\Throwable $e){
			if($id === null){
				return null;
			}
			return $this->error($id, -32603, $e->getMessage());
		}
	}

	private function result($id, $result): array{
		return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
	}

	private function error($id, int $code, string $message): array{
		return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
	}

	private function server_version(): string{
		return is_file($this->entry) ? date('Ymd', (int)filemtime($this->entry)) : '0';
	}

	private function spec(): array{
		if($this->spec === null){
			// @dev エンドポイントを含めるかは実行モードに従う（App が mode を in_mode で出し分けるのと同じ）。
			$include_dev = \ebi\Conf::in_mode('@dev');
			$this->spec = (new \ebi\Dt\OpenApi($this->entry))->generate_spec($this->envelope, $include_dev);
		}
		return $this->spec;
	}

	/**
	 * MCP に公開するツール定義（ドキュメント検索・参照）。
	 */
	private function tool_defs(): array{
		return [
			[
				'name' => 'search_endpoints',
				'description' => 'アプリのAPIエンドポイントをキーワードで検索する（path/summary/description/tag/operationIdが対象）。結果は operationId・method・path・summary の一覧。',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'query' => ['type' => 'string', 'description' => '検索キーワード（空なら全件）'],
						'method' => ['type' => 'string', 'description' => 'HTTPメソッドで絞り込み (get/post/put/patch/delete)'],
						'tag' => ['type' => 'string', 'description' => 'タグ名で絞り込み'],
						'limit' => ['type' => 'integer', 'description' => '最大件数（既定50）'],
					],
				],
			],
			[
				'name' => 'get_endpoint',
				'description' => 'operationId を指定してエンドポイントの詳細（parameters・requestBody・responses と、参照している components schema）を取得する。',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'operationId' => ['type' => 'string', 'description' => 'エンドポイントの operationId'],
					],
					'required' => ['operationId'],
				],
			],
			[
				'name' => 'list_tags',
				'description' => 'API のタグ一覧（グループ）を取得する。',
				'inputSchema' => ['type' => 'object', 'properties' => new \stdClass()],
			],
			[
				'name' => 'get_schema',
				'description' => 'components schema（モデル定義）を名前で取得する。名前は search/get_endpoint の $ref に現れるもの。',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'name' => ['type' => 'string', 'description' => 'スキーマ名（例: \\App\\Model\\User）'],
					],
					'required' => ['name'],
				],
			],
		];
	}

	private function call_tool(string $name, array $args){
		switch($name){
			case 'search_endpoints':
				return $this->tool_json($this->search_endpoints($args));
			case 'get_endpoint':
				return $this->get_endpoint((string)($args['operationId'] ?? ''));
			case 'list_tags':
				return $this->tool_json($this->list_tags());
			case 'get_schema':
				return $this->get_schema((string)($args['name'] ?? ''));
			default:
				return $this->tool_error('unknown tool: '.$name);
		}
	}

	/**
	 * 全オペレーションを走査して {operationId, method, path, summary, tags, deprecated, op} を返す。
	 */
	private function operations(): array{
		$ops = [];
		foreach(($this->spec()['paths'] ?? []) as $path => $methods){
			foreach($methods as $method => $op){
				$ops[] = [
					'operationId' => $op['operationId'] ?? null,
					'method' => strtoupper($method),
					'path' => $path,
					'summary' => $op['summary'] ?? '',
					'tags' => $op['tags'] ?? [],
					'deprecated' => !empty($op['deprecated']),
					'op' => $op,
				];
			}
		}
		return $ops;
	}

	private function search_endpoints(array $args): array{
		$query = trim((string)($args['query'] ?? ''));
		$method = strtolower(trim((string)($args['method'] ?? '')));
		$tag = trim((string)($args['tag'] ?? ''));
		$limit = (int)($args['limit'] ?? 50);
		if($limit <= 0){
			$limit = 50;
		}

		$results = [];
		foreach($this->operations() as $o){
			if($method !== '' && strtolower($o['method']) !== $method){
				continue;
			}
			if($tag !== '' && !in_array($tag, $o['tags'], true)){
				continue;
			}
			if($query !== ''){
				$haystack = mb_strtolower(implode(' ', [
					$o['operationId'] ?? '',
					$o['path'],
					$o['summary'],
					$o['op']['description'] ?? '',
					implode(' ', $o['tags']),
				]));
				if(mb_strpos($haystack, mb_strtolower($query)) === false){
					continue;
				}
			}
			$results[] = [
				'operationId' => $o['operationId'],
				'method' => $o['method'],
				'path' => $o['path'],
				'summary' => $o['summary'],
				'tags' => $o['tags'],
				'deprecated' => $o['deprecated'],
			];
		}

		$total = count($results);
		$results = array_slice($results, 0, $limit);
		return ['total' => $total, 'count' => count($results), 'endpoints' => $results];
	}

	private function get_endpoint(string $operationId){
		if($operationId === ''){
			return $this->tool_error('operationId is required');
		}
		foreach($this->operations() as $o){
			if(($o['operationId'] ?? null) === $operationId){
				$refs = [];
				$this->collect_refs($o['op'], $refs);
				$schemas = [];
				foreach(array_keys($refs) as $name){
					$this->resolve_ref_schema($name, $schemas);
				}
				return $this->tool_json([
					'operationId' => $operationId,
					'method' => $o['method'],
					'path' => $o['path'],
					'operation' => $o['op'],
					'schemas' => empty($schemas) ? new \stdClass() : $schemas,
				]);
			}
		}
		return $this->tool_error('endpoint not found: '.$operationId);
	}

	private function list_tags(): array{
		$spec = $this->spec();
		if(!empty($spec['tags'])){
			return ['tags' => $spec['tags']];
		}
		// tags未定義ならオペレーションから収集
		$names = [];
		foreach($this->operations() as $o){
			foreach($o['tags'] as $t){
				$names[$t] = true;
			}
		}
		return ['tags' => array_map(fn($n) => ['name' => $n], array_keys($names))];
	}

	private function get_schema(string $name){
		if($name === ''){
			return $this->tool_error('name is required');
		}
		$schemas = $this->spec()['components']['schemas'] ?? [];
		foreach([$name, '\\'.ltrim($name, '\\'), ltrim($name, '\\')] as $key){
			if(isset($schemas[$key])){
				return $this->tool_json(['name' => $key, 'schema' => $schemas[$key]]);
			}
		}
		return $this->tool_error('schema not found: '.$name);
	}

	/**
	 * 配列内の $ref を再帰的に収集（スキーマ名 => true）。
	 */
	private function collect_refs($data, array &$refs): void{
		if(!is_array($data)){
			return;
		}
		foreach($data as $key => $value){
			if($key === '$ref' && is_string($value)){
				$refs[str_replace('#/components/schemas/', '', $value)] = true;
			}else if(is_array($value)){
				$this->collect_refs($value, $refs);
			}
		}
	}

	/**
	 * スキーマとその依存を再帰的に解決して $out に積む。
	 */
	private function resolve_ref_schema(string $name, array &$out): void{
		if(isset($out[$name])){
			return;
		}
		$schemas = $this->spec()['components']['schemas'] ?? [];
		if(!isset($schemas[$name])){
			return;
		}
		$out[$name] = $schemas[$name];

		$child = [];
		$this->collect_refs($schemas[$name], $child);
		foreach(array_keys($child) as $c){
			$this->resolve_ref_schema($c, $out);
		}
	}

	private function tool_json($data): array{
		return [
			'content' => [[
				'type' => 'text',
				'text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
			]],
			'isError' => false,
		];
	}

	private function tool_error(string $message): array{
		return [
			'content' => [['type' => 'text', 'text' => $message]],
			'isError' => true,
		];
	}
}
