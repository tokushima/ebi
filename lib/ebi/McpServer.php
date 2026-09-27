<?php
namespace ebi;

/**
 * 単一クラスの `#[McpTool]` を MCP（Model Context Protocol）ツールとして公開するランタイム。
 *
 * `#[McpEndpoint]` を付けたクラスがマウントされると、App の automap がこのランタイムへ振り向ける
 * （対象クラスを与えて serve() を実行）。第三者ブリッジ（npx 等）を介さず ebi 自身が
 * MCP（JSON-RPC 2.0 / Streamable HTTP）を喋る。inputSchema は #[Parameter] から OpenAPI と同一機構で
 * 生成し、実行時は tools/call の arguments を対象インスタンスの入力（in_vars）へ注入して呼ぶ。
 */
class McpServer{
	// 対応するMCPプロトコル版（新しい順）。使うのは tools/list・tools/call の基本サブセットのみで、
	// これらのリビジョン間で安定しているため複数版を対応とする。
	private const PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];

	private string $class;
	private string $entry;

	/** name => ['method'=>string,'description'=>string,'inputSchema'=>array|\stdClass] */
	private ?array $tools = null;

	public function __construct(string $class, string $entry=''){
		$this->class = ltrim($class, '\\');
		$this->entry = ($entry !== '') ? $entry : $this->detect_entry();
	}

	// ────────────────────────────────
	// トランスポート（Streamable HTTP）
	// ────────────────────────────────

	/**
	 * MCP サーバ本体。POST の JSON-RPC 2.0 のみ対応。automap が振り向けた callable から呼ばれる。
	 */
	public function serve(): void{
		if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'){
			// server→client 用の SSE を開こうとする GET 等には 405（再接続ループ防止）
			\ebi\HttpHeader::send('Allow', 'POST');
			\ebi\HttpHeader::send_status(405);
			exit;
		}
		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');

		$payload = json_decode((string)file_get_contents('php://input'), true);
		if(!is_array($payload)){
			echo json_encode(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => 'Parse error']]);
			exit;
		}

		// バッチ(JSON配列) と 単一(JSONオブジェクト) の両対応。
		$is_batch = array_key_exists(0, $payload) && is_array($payload[0]);
		$requests = $is_batch ? $payload : [$payload];

		$responses = [];
		foreach($requests as $req){
			if(!is_array($req)){
				continue;
			}
			$res = $this->handle($req);
			if($res !== null){
				$responses[] = $res;
			}
		}

		if(empty($responses)){
			\ebi\HttpHeader::send_status(202); // 通知のみ（応答不要）
			exit;
		}
		echo json_encode($is_batch ? $responses : $responses[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	// ────────────────────────────────
	// JSON-RPC 2.0（トランスポート非依存。テスト可能な純ハンドラ）
	// ────────────────────────────────

	/**
	 * JSON-RPC 2.0 リクエストを処理して応答配列を返す。通知(idなし)には null（応答不要）。
	 */
	public function handle(array $req): ?array{
		$id = $req['id'] ?? null;
		$method = $req['method'] ?? '';
		$params = $req['params'] ?? [];

		try{
			switch($method){
				case 'initialize':
					// バージョンネゴシエーション: 要求版が対応集合にあればエコー、無ければ既定(最新)。
					$requested = $params['protocolVersion'] ?? null;
					$version = (is_string($requested) && in_array($requested, self::PROTOCOL_VERSIONS, true))
						? $requested
						: self::PROTOCOL_VERSIONS[0];
					return $this->result($id, [
						'protocolVersion' => $version,
						'capabilities' => ['tools' => ['listChanged' => false]],
						'serverInfo' => ['name' => $this->server_name(), 'version' => $this->server_version()],
					]);

				case 'notifications/initialized':
				case 'notifications/cancelled':
					return null;

				case 'ping':
					return $this->result($id, new \stdClass());

				case 'tools/list':
					return $this->result($id, ['tools' => $this->tool_defs()]);

				case 'tools/call':
					$name = (string)($params['name'] ?? '');
					$arguments = $params['arguments'] ?? [];
					return $this->result($id, $this->call_tool($name, is_array($arguments) ? $arguments : []));

				default:
					return $id === null ? null : $this->error($id, -32601, 'Method not found: '.$method);
			}
		}catch(\Throwable $e){
			return $id === null ? null : $this->error($id, -32603, $e->getMessage());
		}
	}

	private function result($id, $result): array{
		return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
	}

	private function error($id, int $code, string $message): array{
		return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
	}

	// ────────────────────────────────
	// ツール（#[McpTool] リフレクション）
	// ────────────────────────────────

	/**
	 * 対象クラスの `#[McpTool]` 付き public メソッドを name => {method,description,inputSchema} で収集する。
	 * 継承メソッドも走査対象だが、`#[McpTool]` を持たないもの（#[Route] 等）は除外される。
	 */
	private function tools(): array{
		if($this->tools === null){
			$this->tools = self::scan($this->class, $this->entry);
		}
		return $this->tools;
	}

	private static function scan(string $class, string $entry): array{
		$class = ltrim($class, '\\');
		$tools = [];
		if(!class_exists($class)){
			return $tools;
		}
		$openapi = new \ebi\Dt\OpenApi($entry);

		foreach((new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $m){
			if($m->isStatic() || $m->isAbstract()){
				continue;
			}
			$method = $m->getName();
			$tool = \ebi\AttributeReader::get_method($class, $method, 'mcp_tool');
			if($tool === null){
				continue; // #[McpTool] 無し
			}
			$name = $tool['name'] ?? $method;
			if(isset($tools[$name])){
				continue;
			}
			$description = $tool['description'] ?? null;
			if($description === null){
				try{
					$description = \ebi\Dt\SourceAnalyzer::method_info($class, $method)->summary();
				}catch(\Throwable $e){
					$description = '';
				}
			}
			$tools[$name] = [
				'method' => $method,
				'description' => (string)$description,
				'inputSchema' => $openapi->build_input_schema($class, $method),
			];
		}
		return $tools;
	}

	/**
	 * 対象クラスが公開する MCP ツールの一覧（name / description / inputSchema）を返す。
	 * OpenApi の x-mcp-endpoints や DevTools UI 等、実行系以外から「どんなツールがあるか」を得るための入口。
	 *
	 * @return array<int,array{name:string,description:string,inputSchema:array|\stdClass}>
	 */
	public static function tools_of(string $class, string $entry=''): array{
		$list = [];
		foreach(self::scan($class, $entry) as $name => $t){
			$list[] = [
				'name' => $name,
				'description' => $t['description'],
				'inputSchema' => $t['inputSchema'],
			];
		}
		return $list;
	}

	private function tool_defs(): array{
		$defs = [];
		foreach($this->tools() as $name => $t){
			$def = ['name' => $name];
			if($t['description'] !== ''){
				$def['description'] = $t['description'];
			}
			$def['inputSchema'] = $t['inputSchema'];
			$defs[] = $def;
		}
		return $defs;
	}

	private function call_tool(string $name, array $args): array{
		$tools = $this->tools();
		if(!isset($tools[$name])){
			return $this->tool_error('unknown tool: '.$name);
		}
		$t = $tools[$name];

		// inputSchema の required を満たしているか（欠落は実行前に弾く）
		$required = is_array($t['inputSchema']) ? ($t['inputSchema']['required'] ?? []) : [];
		$missing = [];
		foreach($required as $key){
			if(!array_key_exists($key, $args) || $args[$key] === null || $args[$key] === ''){
				$missing[] = $key;
			}
		}
		if(!empty($missing)){
			return $this->tool_error('required argument(s) missing: '.implode(', ', $missing));
		}

		try{
			// arguments を対象インスタンスの入力へ注入し、既存アクションが in_vars で読めるようにする。
			$ins = (new \ReflectionClass($this->class))->newInstance();
			if($ins instanceof \ebi\Request){
				foreach($args as $k => $v){
					$ins->vars((string)$k, $v);
				}
			}
			$result = $ins->{$t['method']}();
			return $this->tool_result(is_array($result) ? $result : ['result' => $result]);
		}catch(\Throwable $e){
			return $this->tool_error($e->getMessage());
		}
	}

	private function tool_result($data): array{
		return [
			'content' => [['type' => 'text', 'text' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)]],
			'isError' => false,
		];
	}

	private function tool_error(string $message): array{
		return [
			'content' => [['type' => 'text', 'text' => $message]],
			'isError' => true,
		];
	}

	// ────────────────────────────────
	// メタ
	// ────────────────────────────────

	/** エントリファイル（App/Flow の呼び出し元）を backtrace で特定する（inputSchema/serverInfo 用）。 */
	private function detect_entry(): string{
		foreach(debug_backtrace(false) as $t){
			if(isset($t['class'], $t['file']) && ($t['class'] === 'ebi\\App' || $t['class'] === 'ebi\\Flow')){
				return $t['file'];
			}
		}
		return '';
	}

	private function server_name(): string{
		return $this->entry !== '' ? basename($this->entry, '.php') : $this->class;
	}

	private function server_version(): string{
		return ($this->entry !== '' && is_file($this->entry)) ? date('Ymd', (int)filemtime($this->entry)) : '0';
	}
}
