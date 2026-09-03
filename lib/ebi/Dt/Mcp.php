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
				'name' => 'api_info',
				'description' => 'API の概要を返す＝クライアント実装の起点。info(title/version/description)・servers(base URL)・securitySchemes(認証方式の定義)・security(グローバル既定)。各エンドポイントの security 参照名はここの securitySchemes で解決する。',
				'inputSchema' => ['type' => 'object', 'properties' => new \stdClass()],
			],
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
			[
				'name' => 'list_flows',
				'description' => '達成できるゴール（produce される状態/値トークン）の一覧を返す＝ユースケース発見用。各要素は goal(トークン名)・kind・summary・tag・producedBy(その状態/値を成立させるAPI)。ここから goal を選び get_flow に渡すと到達手順(plan)が得られる。',
				'inputSchema' => ['type' => 'object', 'properties' => new \stdClass()],
			],
			[
				'name' => 'get_flow',
				'description' => 'goal（operationId か 状態トークン）に到達するための呼び出し順（plan）を、各エンドポイントの前提(#[Requires])と効果(#[Produces])から導出する。plan=必須の本筋(hard requiresの連鎖)、optionalSteps=本筋に差し込める任意の中間段(soft requires/#[Follows]で本筋に接続、afterStep=推奨挿入位置)、inputs=事前に必要な入力(ambient等)、branches=分岐(when≠success)、alternatives=代替経路、issues=関係するgate違反。',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'goal' => ['type' => 'string', 'description' => 'ゴール。operationId か、成立させたい状態トークン（x-flow-registry のトークン名）'],
					],
					'required' => ['goal'],
				],
			],
		];
	}

	private function call_tool(string $name, array $args){
		switch($name){
			case 'api_info':
				return $this->tool_json($this->api_info());
			case 'search_endpoints':
				return $this->tool_json($this->search_endpoints($args));
			case 'get_endpoint':
				return $this->get_endpoint((string)($args['operationId'] ?? ''));
			case 'list_tags':
				return $this->tool_json($this->list_tags());
			case 'get_schema':
				return $this->get_schema((string)($args['name'] ?? ''));
			case 'list_flows':
				return $this->tool_json($this->list_flows());
			case 'get_flow':
				return $this->get_flow(is_array($args) ? $args : []);
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

	/**
	 * API 概要（info・servers・securitySchemes・グローバル security）を返す＝クライアント実装の起点。
	 */
	private function api_info(): array{
		$spec = $this->spec();
		return array_filter([
			'info' => $spec['info'] ?? null,
			'servers' => $spec['servers'] ?? null,
			'security' => $spec['security'] ?? null,
			'securitySchemes' => $spec['components']['securitySchemes'] ?? null,
		], fn($v) => $v !== null && $v !== []);
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

	/**
	 * goal（operationId か token）へ到達する呼び出し順を x-flow から導出する。
	 */
	/**
	 * 達成できるゴール（produce される状態/値トークン）の一覧を返す。get_flow の入口＝ユースケース発見用。
	 */
	private function list_flows(): array{
		$spec = $this->spec();
		$registry = $spec['x-flow-registry'] ?? [];
		$producers = [];
		foreach(($spec['paths'] ?? []) as $path => $methods){
			foreach($methods as $method => $op){
				$flow = $op['x-flow'] ?? null;
				if(empty($flow['produces'])){
					continue;
				}
				$oid = $op['operationId'] ?? (strtoupper($method).' '.$path);
				$info = [
					'operationId' => $oid,
					'method' => strtoupper($method),
					'path' => $path,
					'tag' => $op['tags'][0] ?? null,
					'deprecated' => !empty($op['deprecated']),
				];
				foreach($flow['produces'] as $p){
					if(isset($p['token'])){
						$producers[$p['token']][$oid] = $info;
					}
				}
			}
		}
		// バッチ(cron)アクターの生産者も含める
		foreach(($spec['x-flow-batches'] ?? []) as $b){
			$oid = $b['operationId'] ?? null;
			if($oid === null || empty($b['x-flow']['produces'])){
				continue;
			}
			$info = ['operationId' => $oid, 'method' => 'BATCH', 'path' => null, 'tag' => null, 'actor' => 'batch', 'deprecated' => false];
			foreach($b['x-flow']['produces'] as $p){
				if(isset($p['token'])){
					$producers[$p['token']][$oid] = $info;
				}
			}
		}
		$flows = [];
		foreach($producers as $token => $by){
			// active（非 deprecated）の生産者を優先、無ければ全件
			$active = array_filter($by, fn($o) => empty($o['deprecated']));
			$use = !empty($active) ? $active : $by;
			$tag = null;
			foreach($use as $o){
				if($o['tag'] !== null){
					$tag = $o['tag'];
					break;
				}
			}
			$flows[] = array_filter([
				'goal' => $token,
				'kind' => $registry[$token]['kind'] ?? null,
				'summary' => $registry[$token]['summary'] ?? null,
				'tag' => $tag,
				'producedBy' => array_values(array_map(fn($o) => array_filter([
					'operationId' => $o['operationId'],
					'method' => $o['method'],
					'path' => $o['path'],
					'actor' => $o['actor'] ?? null,
				], fn($v) => $v !== null), $use)),
			], fn($v) => $v !== null && $v !== []);
		}
		usort($flows, fn($a, $b) => [$a['tag'] ?? '~', $a['goal']] <=> [$b['tag'] ?? '~', $b['goal']]);
		return ['flows' => $flows];
	}

	private function get_flow(array $args){
		$goal = trim((string)($args['goal'] ?? ''));
		if($goal === ''){
			return $this->tool_error('goal is required (operationId or token)');
		}
		$spec = $this->spec();
		$registry = $spec['x-flow-registry'] ?? [];

		// x-flow を持つ operation を収集し、生産者索引 token=>[operationId] を作る
		$ops = [];
		$producers = [];
		foreach(($spec['paths'] ?? []) as $path => $methods){
			foreach($methods as $method => $op){
				$flow = $op['x-flow'] ?? null;
				if(empty($flow)){
					continue;
				}
				$oid = $op['operationId'] ?? (strtoupper($method).' '.$path);

				$req = [];
				foreach(($flow['requires'] ?? []) as $r){
					if(isset($r['token'])){
						$req[] = $r['token'];
					}
				}
				$pro = [];
				foreach(($flow['produces'] ?? []) as $p){
					if(isset($p['token'])){
						$pro[] = ['token' => $p['token'], 'when' => $p['when'] ?? 'success'];
						$producers[$p['token']][$oid] = true;
					}
				}
				$ops[$oid] = [
					'method' => strtoupper($method),
					'path' => $path,
					'summary' => $op['summary'] ?? '',
					'requires' => $req,
					'produces' => $pro,
					'requiresRaw' => $flow['requires'] ?? [],
					'follows' => $flow['follows'] ?? [],
					'deprecated' => !empty($op['deprecated']),
				];
			}
		}

		// バッチ(cron)アクターも同じ索引に含める（呼び出し不可の状態遷移。actor=cron を付与）。
		foreach(($spec['x-flow-batches'] ?? []) as $b){
			$flow = $b['x-flow'] ?? null;
			$oid = $b['operationId'] ?? null;
			if(empty($flow) || $oid === null){
				continue;
			}
			$req = [];
			foreach(($flow['requires'] ?? []) as $r){
				if(isset($r['token'])){
					$req[] = $r['token'];
				}
			}
			$pro = [];
			foreach(($flow['produces'] ?? []) as $p){
				if(isset($p['token'])){
					$pro[] = ['token' => $p['token'], 'when' => $p['when'] ?? 'success'];
					$producers[$p['token']][$oid] = true;
				}
			}
			$ops[$oid] = [
				'method' => 'BATCH',
				'path' => null,
				'summary' => '',
				'requires' => $req,
				'produces' => $pro,
				'requiresRaw' => $flow['requires'] ?? [],
				'follows' => $flow['follows'] ?? [],
				'deprecated' => false,
				'actor' => 'batch',
				'name' => $b['name'] ?? $oid,
			];
		}

		// token の生産者は active（非 deprecated）を優先し、active が無い時だけ deprecated にフォールバックする。
		$active_producers = function(string $t) use (&$producers, &$ops): array{
			$all = array_keys($producers[$t] ?? []);
			$active = array_values(array_filter($all, fn($p) => empty($ops[$p]['deprecated'])));
			return empty($active) ? $all : $active;
		};

		// goal を operationId か token として解決
		if(isset($ops[$goal])){
			$goal_ops = [$goal];
			$resolved_as = 'operationId';
		}else if(isset($producers[$goal])){
			$goal_ops = $active_producers($goal);
			$resolved_as = 'token';
		}else{
			return $this->tool_error("goal '{$goal}' が operationId としても produces token としても解決できません");
		}

		// 後ろ向き閉包: hard requires の token を辿り生産者を集める。ambient/未生産は inputs へ。
		$needed = [];
		$inputs = [];
		$alternatives = [];
		$stack = $goal_ops;
		$guard = 0;
		while(!empty($stack) && $guard++ < 1000){
			$oid = array_pop($stack);
			if(isset($needed[$oid])){
				continue;
			}
			$needed[$oid] = true;
			foreach($ops[$oid]['requiresRaw'] as $r){
				$t = $r['token'] ?? null;
				if($t === null || !empty($r['optional'])){
					continue;
				}
				if(!empty($registry[$t]['ambient']) || (($registry[$t]['kind'] ?? '') === 'ambient')){
					$inputs[$t] = 'ambient';
					continue;
				}
				$prod = $active_producers($t);
				if(empty($prod)){
					$inputs[$t] = 'no-producer';
					continue;
				}
				if(count($prod) > 1){
					$alternatives[$t] = $prod;
				}
				foreach($prod as $p){
					if(!isset($needed[$p])){
						$stack[] = $p;
					}
				}
			}
		}

		$plan = $this->flow_topo_order(array_keys($needed), $ops, $producers);

		$plan_out = [];
		$branches = [];
		foreach($plan as $i => $oid){
			$entry = [
				'step' => $i + 1,
				'operationId' => $oid,
				'method' => $ops[$oid]['method'],
				'path' => $ops[$oid]['path'],
				'summary' => $ops[$oid]['summary'],
				'requires' => $ops[$oid]['requires'],
				'produces' => array_map(fn($p) => $p['token'], $ops[$oid]['produces']),
			];
			if(($ops[$oid]['actor'] ?? 'http') === 'batch'){
				// バッチ段: 呼び出し不可（システムが cron で自動実行）
				$entry['actor'] = 'batch';
				$entry['callable'] = false;
			}
			$plan_out[] = $entry;
			foreach($ops[$oid]['produces'] as $p){
				if(($p['when'] ?? 'success') !== 'success'){
					$branches[] = ['at' => $i + 1, 'operationId' => $oid, 'when' => $p['when'], 'token' => $p['token']];
				}
			}
		}

		$input_list = [];
		foreach($inputs as $t => $reason){
			$input_list[] = ['token' => $t, 'kind' => ($registry[$t]['kind'] ?? 'unknown'), 'reason' => $reason];
		}

		// hard plan（spine）は維持しつつ、この flow に差し込める任意の中間段を optionalSteps として提示する。
		$optional_steps = $this->flow_optional_steps($needed, $ops, $plan_out, $inputs, $registry);

		// spine op の optional requires（one-of 等）の token を作る「上流の入口候補」を提示する。
		$entry_options = $this->flow_entry_options($needed, $ops, $plan_out, $producers, $registry, $active_producers);

		$rel_issues = [];
		foreach(($spec['x-flow-issues'] ?? []) as $iss){
			if(isset($needed[$iss['operationId'] ?? ''])){
				$rel_issues[] = $iss;
			}
		}

		return $this->tool_json(array_filter([
			'goal' => $goal,
			'resolvedAs' => $resolved_as,
			'inputs' => $input_list,
			'entryOptions' => empty($entry_options) ? null : $entry_options,
			'plan' => $plan_out,
			'optionalSteps' => empty($optional_steps) ? null : $optional_steps,
			'branches' => $branches,
			'alternatives' => empty($alternatives) ? null : $alternatives,
			'issues' => empty($rel_issues) ? null : $rel_issues,
		], fn($v) => $v !== null && $v !== []));
	}

	/**
	 * spine 各 op の optional requires（one-of の値トークン等。後ろ向き閉包は optional を辿らないため
	 * hard spine には現れない）について、その token を作る生産者 op を「上流の入口候補」として列挙する。
	 * plan 内で既に生産される token / ambient / 生産者が spine 内 / 生産者なし は除外する。
	 * @return array<int,array{token:string,forOperationId:string,forStep:?int,producers:array}>
	 */
	private function flow_entry_options(array $needed, array $ops, array $plan_out, array $producers, array $registry, callable $active_producers): array{
		$plan_produced = [];
		$oid_step = [];
		foreach($plan_out as $po){
			$oid_step[$po['operationId']] = $po['step'];
			foreach($po['produces'] as $t){
				$plan_produced[$t] = true;
			}
		}
		$is_ambient = function(string $t) use ($registry): bool{
			return !empty($registry[$t]['ambient']) || (($registry[$t]['kind'] ?? '') === 'ambient');
		};

		$out = [];
		$seen = [];
		foreach(array_keys($needed) as $oid){
			foreach($ops[$oid]['requiresRaw'] as $r){
				if(empty($r['optional'])){
					continue; // hard は spine 側で解決済み
				}
				$t = $r['token'] ?? null;
				if($t === null || $is_ambient($t) || isset($plan_produced[$t])){
					continue;
				}
				// spine 外に生産者があるか（無ければ入口候補にならない）
				$rest = array_values(array_filter($active_producers($t), fn($p) => !isset($needed[$p])));
				if(empty($rest)){
					continue;
				}
				$key = $oid.'|'.$t;
				if(isset($seen[$key])){
					continue;
				}
				$seen[$key] = true;
				// token を作る上流チェーン全体（後ろ向き閉包＋topo整列）。多段の入口を1本で見せる。
				$chain = $this->flow_producer_chain($t, $ops, $producers, $registry, $active_producers);
				$out[] = [
					'token' => $t,
					'forOperationId' => $oid,
					'forStep' => $oid_step[$oid] ?? null,
					'chain' => array_map(fn($p) => [
						'operationId' => $p,
						'method' => $ops[$p]['method'],
						'path' => $ops[$p]['path'],
						'summary' => $ops[$p]['summary'],
						'produces' => array_map(fn($x) => $x['token'], $ops[$p]['produces']),
					], $chain),
				];
			}
		}
		usort($out, fn($a, $b) => [$a['forStep'] ?? 0, $a['token']] <=> [$b['forStep'] ?? 0, $b['token']]);
		return $out;
	}

	/**
	 * token を作るための op 連鎖を後ろ向き閉包（hard requires を辿る。ambient/生産者なしで停止）し、
	 * flow_topo_order で整列して operationId 列（root→直接生産者）を返す。entryOptions の多段表示用。
	 */
	private function flow_producer_chain(string $token, array $ops, array $producers, array $registry, callable $active_producers): array{
		$needed = [];
		$stack = $active_producers($token);
		$guard = 0;
		while(!empty($stack) && $guard++ < 1000){
			$oid = array_pop($stack);
			if($oid === null || isset($needed[$oid]) || !isset($ops[$oid])){
				continue;
			}
			$needed[$oid] = true;
			foreach($ops[$oid]['requiresRaw'] as $r){
				$t = $r['token'] ?? null;
				if($t === null || !empty($r['optional'])){
					continue;
				}
				if(!empty($registry[$t]['ambient']) || (($registry[$t]['kind'] ?? '') === 'ambient')){
					continue;
				}
				foreach($active_producers($t) as $p){
					if(!isset($needed[$p])){
						$stack[] = $p;
					}
				}
			}
		}
		return $this->flow_topo_order(array_keys($needed), $ops, $producers);
	}

	/**
	 * hard plan（spine）に対して「差し込み可能な任意の中間段」を導出する。
	 * soft requires（optional:true）の token が plan の産物で満たされる、
	 * または #[Follows] が plan op を指す op を、推奨挿入位置(afterStep)付きで列挙する。
	 * hard requires は plan産物 / inputs / ambient で満たせるものだけを対象とする（満たせない=別フロー）。
	 */
	private function flow_optional_steps(array $needed, array $ops, array $plan_out, array $inputs, array $registry): array{
		// plan の各 op が生産する token => 最小 step、operationId => step
		$token_step = [];
		$oid_step = [];
		foreach($plan_out as $po){
			$oid_step[$po['operationId']] = $po['step'];
			foreach($po['produces'] as $t){
				if(!isset($token_step[$t]) || $po['step'] < $token_step[$t]){
					$token_step[$t] = $po['step'];
				}
			}
		}
		// 利用可能 token: plan 産物 ∪ inputs
		$available = [];
		foreach($token_step as $t => $s){
			$available[$t] = true;
		}
		foreach($inputs as $t => $r){
			$available[$t] = true;
		}
		$is_ambient = function(string $t) use ($registry): bool{
			return !empty($registry[$t]['ambient']) || (($registry[$t]['kind'] ?? '') === 'ambient');
		};

		$steps = [];
		$chosen = [];
		$guard = 0;
		// 不動点反復: 採用した任意段の産物を available に足し、それに依存する任意段を次passで拾う（多段接続）。
		do{
			$added = false;
			foreach($ops as $oid => $o){
				if(isset($needed[$oid]) || isset($chosen[$oid])){
					continue; // spine か採用済み
				}
				if(!empty($o['deprecated'])){
					continue; // deprecated は任意段に出さない
				}
				$hard_ok = true;
				$soft_link = []; // plan/任意段 産物で満たされる soft token（この flow への接続根拠）
				$after_step = 0;
				foreach($o['requiresRaw'] as $r){
					$t = $r['token'] ?? null;
					if($t === null){
						continue;
					}
					if(empty($r['optional'])){
						// hard require: この flow の文脈で満たせなければ対象外
						if(!isset($available[$t]) && !$is_ambient($t)){
							$hard_ok = false;
							break;
						}
						if(isset($token_step[$t])){
							$after_step = max($after_step, $token_step[$t]);
						}
					}else if(isset($token_step[$t])){
						// soft require が既知産物で満たされる → 接続根拠かつ位置ヒント
						$soft_link[] = $t;
						$after_step = max($after_step, $token_step[$t]);
					}
				}
				if(!$hard_ok){
					continue;
				}
				// #[Follows] が plan/任意段 op を指すなら接続根拠 & 位置ヒント
				$follows_hits = [];
				foreach($o['follows'] as $a){
					$ep = $a['endpoint'] ?? null;
					if($ep !== null && isset($oid_step[$ep])){
						$follows_hits[] = $ep;
						$after_step = max($after_step, $oid_step[$ep]);
					}
				}
				// この flow に接続していない op（無関係な soft 消費者）は出さない
				if(empty($soft_link) && empty($follows_hits)){
					continue;
				}
				$chosen[$oid] = true;
				$added = true;
				$produced = array_map(fn($p) => $p['token'], $o['produces']);
				$steps[] = [
					'operationId' => $oid,
					'method' => $o['method'],
					'path' => $o['path'],
					'summary' => $o['summary'],
					'requires' => $o['requires'],
					'produces' => $produced,
					'afterStep' => $after_step,
					'linkedBy' => array_values(array_unique(array_merge(
						array_map(fn($t) => 'requires:'.$t, $soft_link),
						array_map(fn($e) => 'follows:'.$e, $follows_hits)
					))),
				];
				// 産物を available に加える（挿入位置以降で利用可）。多段接続の次pass用。
				$pos = $after_step + 1;
				$oid_step[$oid] = $pos;
				foreach($produced as $t){
					if(!isset($token_step[$t]) || $pos < $token_step[$t]){
						$token_step[$t] = $pos;
					}
					$available[$t] = true;
				}
			}
		}while($added && $guard++ < 100);

		// afterStep, operationId で安定ソート
		usort($steps, fn($a, $b) => [$a['afterStep'], $a['operationId']] <=> [$b['afterStep'], $b['operationId']]);
		return $steps;
	}

	/**
	 * 生産者→消費者の依存で安定トポロジカル整列（Kahn法）。循環時は残りを後置。
	 */
	private function flow_topo_order(array $oids, array $ops, array $producers): array{
		$set = array_flip($oids);
		$indeg = [];
		$edges = [];
		foreach($oids as $oid){
			$indeg[$oid] = 0;
		}
		foreach($oids as $oid){
			foreach($ops[$oid]['requiresRaw'] as $r){
				$t = $r['token'] ?? null;
				if($t === null || !empty($r['optional'])){
					continue;
				}
				foreach(array_keys($producers[$t] ?? []) as $p){
					if($p === $oid || !isset($set[$p])){
						continue;
					}
					$edges[$p][] = $oid;
					$indeg[$oid]++;
				}
			}
		}
		$queue = [];
		foreach($oids as $oid){
			if($indeg[$oid] === 0){
				$queue[] = $oid;
			}
		}
		sort($queue);
		$order = [];
		$seen = [];
		while(!empty($queue)){
			$oid = array_shift($queue);
			if(isset($seen[$oid])){
				continue;
			}
			$seen[$oid] = true;
			$order[] = $oid;
			$next = [];
			foreach(($edges[$oid] ?? []) as $c){
				if(--$indeg[$c] === 0){
					$next[] = $c;
				}
			}
			sort($next);
			foreach($next as $n){
				$queue[] = $n;
			}
		}
		foreach($oids as $oid){
			if(!isset($seen[$oid])){
				$order[] = $oid;
			}
		}
		return $order;
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
