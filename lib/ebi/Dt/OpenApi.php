<?php
namespace ebi\Dt;
/**
 * OpenAPI仕様を生成する
 */
class OpenApi extends \ebi\app\Request{
	private string $entry;

	public function __construct(?string $entryfile=null){
		if(empty($entryfile)){
			$trace = debug_backtrace(false);
			krsort($trace);

			foreach($trace as $t){
				if(isset($t['class']) && ($t['class'] == 'ebi\App' || $t['class'] == 'ebi\Flow')){
					$this->entry = $t['file'];
					break;
				}
			}
		}else{
			$entryfile = realpath($entryfile);
			$this->entry = $entryfile;
		}
		parent::__construct();
	}

	/**
	 * OpenAPI JSON
	 * @automap
	 */
	public function index(): void{
		$spec = $this->generate_spec();

		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');
		print(json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
		exit;
	}

	/**
	 * OpenAPI仕様を生成する
	 */
	private bool $envelope = false;
	private array $webhooks = [];
	private array $all_tags = [];
	private array $skipped = [];
	/** #[FlowToken] で宣言された、生産者を持たない ambient トークンの定義 token=>def */
	private array $flow_token_decls = [];
	private bool $auto_throws = true;

	public function generate_spec(bool $envelope=false, bool $include_dev=false): array{
		$this->envelope = $envelope;

		/**
		 * @var bool
		 * ソースの throw new から自動検出した例外も 4xx レスポンスに含める（既定 true）。
		 * false にすると @throws / #[Throws] / #[ErrorResponse] で明示宣言したものだけになる。
		 */
		$this->auto_throws = (bool)\ebi\Conf::get('openapi_auto_throws', true);
		$this->webhooks = [];
		$map = \ebi\App::get_map($this->entry);
		$patterns = $map['patterns'];
		unset($map['patterns']);

		$req = new \ebi\Request();
		$target_version = (string)$req->in_vars('version');
		$file_version = date('Ymd', filemtime($this->entry));
		$self_class = static::class;

		$class_name = function($name){
			return ($name[0] === '\\') ? substr($name, 1) : $name;
		};

		$entry_desc = (preg_match('/\/\*\*.+?\*\//s', \ebi\Util::file_read($this->entry), $m)) ?
			trim(preg_replace("/^[\s]*\*[\s]{0,1}/m", '', str_replace(['/'.'**', '*'.'/'], '', $m[0]))) :
			'';

		/**
		 * @param string $title APIタイトル
		 */
		$title = \ebi\Conf::get('title', basename($this->entry, '.php'));

		/**
		 * @param string $api_version APIバージョン
		 */
		$api_version = \ebi\Conf::get('api_version', $target_version ?: $file_version);

		$spec = [
			'openapi' => '3.0.3',
			'info' => [
				'title' => $title,
				'version' => $api_version,
			],
			'paths' => [],
			'components' => [
				'schemas' => [],
			],
		];

		if(preg_match('/@accept\s+(\S+)/',$entry_desc,$accept_m)){
			$spec['x-accept'] = $accept_m[1];
			$entry_desc = trim(preg_replace('/@accept\s+\S+/','',$entry_desc));
		}
		if(!empty($entry_desc)){
			$spec['info']['description'] = $entry_desc;
		}

		/**
		 * @param string[] $servers サーバーURL一覧
		 */
		$servers = \ebi\Conf::gets('servers');
		if(empty($servers)){
			// Conf未設定のときは dt を実行中のサーバー（現在のリクエスト）を1件補完する。
			// scheme+hostはリクエスト由来、ベースパスはアプリのマウントパス(app_url)から取る。
			$host = \ebi\Request::host();
			if(!empty($host)){
				$base_path = rtrim(str_replace('*', '', (string)parse_url((string)\ebi\App::app_url(), PHP_URL_PATH)), '/');
				$servers = [rtrim($host.$base_path, '/')];
			}
		}
		if(!empty($servers)){
			$spec['servers'] = [];
			foreach($servers as $server){
				$spec['servers'][] = ['url' => $server];
			}
		}

		$schemas = [];
		$has_security = false;
		$tags = [];

		$name_to_path = [];
		foreach($patterns as $url_pattern => $p){
			if(isset($p['name'])){
				$name_to_path[$p['name']] = $this->convert_to_openapi_path($url_pattern);
			}
		}

		foreach($patterns as $url_pattern => $m){
			foreach([
				'deprecated' => false,
				'mode' => null,
				'summary' => null,
				'template' => null,
				'version' => null,
			] as $i => $d){
				if(!isset($m[$i])){
					$m[$i] = $d;
				}
			}

			if(isset($m['action']) && is_string($m['action'])){
				[$m['class'], $m['method']] = explode('::', $m['action']);
			}

			if(!isset($m['class']) || $class_name($m['class']) != $self_class){
				try{
					$info = null;
					$http_method = 'get';

					if(isset($m['method'])){
						$info = \ebi\Dt\SourceAnalyzer::method_info($m['class'], $m['method'], true, true);

						if(!isset($m['version'])){
							$m['version'] = $info->version();
						}
						if(empty($m['summary'])){
							[$summary] = explode(PHP_EOL, $info->document());
							$m['summary'] = empty($summary) ? null : $summary;
						}
						if($m['deprecated'] || $info->opt('deprecated')){
							$m['deprecated'] = true;
						}

						// #[HttpMethod]属性を優先、なければDocBlock/@http_method
						$http_method_attr = \ebi\AttributeReader::get_method($m['class'], $m['method'], 'http_method');
						if(!empty($http_method_attr['value'])){
							$http_method = strtolower($http_method_attr['value']);
						}else{
							$http_method = strtolower($info->opt('http_method') ?? 'get');
						}
						if(empty(trim($http_method))){
							$http_method = 'get';
						}

						// do_loginの場合、authクラスのlogin_conditionの@http_methodを参照
						if($m['method'] === 'do_login' && $http_method === 'get'){
							$login_auth_class = $m['auth'] ?? null;
							if(empty($login_auth_class) && isset($m['class'])){
								try{
									$ref = new \ReflectionMethod($m['class'], '__construct');
									$src = \ebi\Dt\SourceAnalyzer::method_src($ref);
									if(preg_match('/set_auth_object\(\s*new\s+([\\\\\w]+)/', $src, $auth_match)){
										$login_auth_class = $auth_match[1];
									}
								}catch(\ReflectionException $e){
								}
							}
							if(!empty($login_auth_class)){
								try{
									$auth_method_info = \ebi\Dt\SourceAnalyzer::method_info($login_auth_class, 'login_condition', true, false);
									$auth_http_method = $auth_method_info->opt('http_method');
									if(!empty($auth_http_method)){
										$http_method = strtolower($auth_http_method);
									}
								}catch(\Exception $e){
								}
							}
						}
					}

					if(!isset($m['version'])){
						$m['version'] = $file_version;
					}

					if(!empty($target_version) && $m['version'] != $target_version){
						continue;
					}

					// @devエンドポイントの処理
					if($m['mode'] === '@dev' && !$include_dev){
						continue;
					}

					// パラメータ名を抽出（正規表現グループの変換用）
					$param_names = [];
					if(isset($info)){
						foreach($info->params() as $param){
							$param_names[] = $param->name();
						}
					}

					// @s2sエンドポイントはwebhookとして収集（メソッドまたはクラスのDocBlock）
					$is_s2s = (isset($info) && $info->opt('s2s'));
					if(!$is_s2s && isset($m['class'])){
						try{
							$class_info = \ebi\Dt\SourceAnalyzer::class_info($m['class']);
							$is_s2s = !!$class_info->opt('s2s');
						}catch(\Exception $e){
						}
					}
					if($is_s2s){
						$path = $this->convert_to_openapi_path($url_pattern, $param_names);
						$operation = $this->build_operation($m, $info, $schemas, $has_security, $tags, $name_to_path, $http_method);

						$this->webhooks[] = [
							'path' => $path,
							'method' => strtoupper($http_method),
							'op' => $operation,
						];
						continue;
					}

					$path = $this->convert_to_openapi_path($url_pattern, $param_names);
					$operation = $this->build_operation($m, $info, $schemas, $has_security, $tags, $name_to_path, $http_method);

					if(!isset($spec['paths'][$path])){
						$spec['paths'][$path] = [];
					}
					$spec['paths'][$path][$http_method] = $operation;

				}catch(\Throwable $e){
					// 生成に失敗してもエンドポイントを「無言で」落とすと、丸ごと消えても気づけない。
					// ログに残しつつ、スペックの x-skipped にも載せて DevTools UI 上で警告表示する。
					$action = ($m['class'] ?? '?').'::'.($m['method'] ?? '?');
					$this->skipped[] = [
						'path'   => $url_pattern ?? '?',
						'action' => $action,
						'error'  => get_class($e).': '.$e->getMessage(),
					];
					\ebi\Log::warning(sprintf(
						'OpenAPI: skipped endpoint %s (%s): %s: %s',
						$url_pattern ?? '?',
						$action,
						get_class($e),
						$e->getMessage()
					));
				}
			}
		}

		// 全てのDaoクラスをスキーマに追加
		foreach(\ebi\Dt::classes(\ebi\Dao::class) as $class_info){
			$this->build_model_schema($class_info['class'], $schemas);
		}

		// 全タグを保存（DevTools UI用）
		$this->all_tags = array_values($tags);

		// pathsで使用されているタグのみを含める
		$used_tags = [];
		foreach($spec['paths'] as $methods){
			foreach($methods as $op){
				foreach($op['tags'] ?? [] as $tag_name){
					$used_tags[$tag_name] = true;
				}
			}
		}
		if(!empty($used_tags)){
			$spec['tags'] = array_values(array_filter($tags, fn($t) => isset($used_tags[$t['name']])));
		}

		// スキーマ名でソート
		ksort($schemas);

		if(!empty($schemas)){
			$spec['components']['schemas'] = $schemas;
		}

		if($has_security){
			$spec['components']['securitySchemes'] = [
				'sessionAuth' => [
					'type' => 'apiKey',
					'in' => 'cookie',
					'name' => 'session',
					'description' => 'Session-based authentication',
				],
			];
		}

		if(empty($spec['components']['schemas']) && empty($spec['components']['securitySchemes'])){
			unset($spec['components']);
		}

		// 生成時にスキップしたエンドポイントを露出（DevTools UI で警告表示する）。
		if(!empty($this->skipped)){
			$spec['x-skipped'] = $this->skipped;
		}

		// flow token: 属性から辞書を構築し G1..G6 を検証、registry/issue をトップレベル露出（flow宣言が無ければ無効）。
		$this->flow_finalize($spec);

		return $spec;
	}

	/**
	 * 各operationの x-flow から token 辞書を構築（#[Produces] が定義、#[FlowToken] が生産者なし語彙）し、
	 * G1..G6 を検証して `x-flow-registry`（トークン定義）と `x-flow-issues`（違反一覧）を spec に付与する。
	 */
	private function flow_finalize(array &$spec): void{
		$schemas = $spec['components']['schemas'] ?? [];

		// operationId => operation（x-flowを持つもの）／生産者索引 token=>[operationId]／全operationId集合／
		// トークン辞書($tokens)を構築する。辞書は「生産箇所(#[Produces])が自らを定義」する原則で、
		// produces から kind/summary を集約する（kind は明示 > via推論: response:*→value / それ以外→state。先勝ち）。
		$ops = [];
		$producers = [];
		$op_ids = [];
		$tokens = [];
		foreach(($spec['paths'] ?? []) as $path => $methods){
			foreach($methods as $method => $op){
				$oid = $op['operationId'] ?? ($method.' '.$path);
				$op_ids[$oid] = true;
				if(empty($op['x-flow'])){
					continue;
				}
				$ops[$oid] = $op;
				foreach(($op['x-flow']['produces'] ?? []) as $p){
					if(!isset($p['token'])){
						continue;
					}
					$t = $p['token'];
					$producers[$t][$oid] = true;
					if(!isset($tokens[$t])){
						$via = (string)($p['via'] ?? '');
						$tokens[$t] = array_filter([
							'kind' => $p['kind'] ?? (strncmp($via, 'response:', 9) === 0 ? 'value' : 'state'),
							'summary' => $p['summary'] ?? null,
						], fn($v) => $v !== null);
					}
				}
			}
		}

		// 生産者を持たない ambient トークン（#[FlowToken]）を辞書へマージ
		foreach($this->flow_token_decls as $t => $def){
			if(!isset($tokens[$t])){
				$tokens[$t] = $def;
			}
		}

		// flow の宣言が一切無ければ機能オフ（何もしない）
		if(empty($ops) && empty($this->flow_token_decls)){
			return;
		}

		// session.user は #[Login] から自動前提化される組込トークン。注釈は不要で、参照時に辞書へ補完する。
		foreach($ops as $op){
			foreach(($op['x-flow']['requires'] ?? []) as $r){
				if(($r['token'] ?? null) === 'session.user' && !isset($tokens['session.user'])){
					$tokens['session.user'] = ['kind' => 'state', 'ambient' => true, 'summary' => 'ログイン済みセッション'];
				}
			}
		}

		$issues = [];
		$add = function(string $gate, string $oid, string $msg) use (&$issues){
			$issues[] = ['gate' => $gate, 'operationId' => $oid, 'message' => $msg];
		};

		foreach($ops as $oid => $op){
			$flow = $op['x-flow'];
			$req_tokens = [];

			foreach(($flow['requires'] ?? []) as $r){
				$t = $r['token'] ?? null;
				if($t === null){
					continue;
				}
				$req_tokens[$t] = true;

				if(!isset($tokens[$t])){                                    // G1
					$add('G1', $oid, "requires token '{$t}' が未定義（生産する #[Produces] も #[FlowToken] 宣言も無い。typoの可能性）");
					continue;
				}
				$is_ambient = !empty($tokens[$t]['ambient']) || (($tokens[$t]['kind'] ?? '') === 'ambient');
				$optional = !empty($r['optional']);
				if(!$optional && !$is_ambient && empty($producers[$t])){     // G2
					$add('G2', $oid, "hard requires '{$t}' の生産者(#[Produces])が存在しない");
				}
				if(isset($r['bind']) && !$this->flow_has_request_field($op, (string)$r['bind'], $schemas)){ // G4
					$add('G4', $oid, "bind '{$r['bind']}' に対応する #[Parameter] が無い");
				}
			}

			foreach(($flow['produces'] ?? []) as $p){
				$t = $p['token'] ?? null;
				if($t === null){
					continue;
				}
				// produces トークンは生産箇所が自らの定義なので G1（未定義）は起き得ない。
				$via = $p['via'] ?? null;
				if(is_string($via) && strncmp($via, 'response:', 9) === 0){  // G3
					$rname = substr($via, 9);
					if(!$this->flow_has_response_field($op, $rname, $schemas)){
						$add('G3', $oid, "via 'response:{$rname}' に対応する #[Response] が無い");
					}
				}
				if(isset($req_tokens[$t]) && ($p['when'] ?? 'success') === 'success'){ // G6
					$add('G6', $oid, "token '{$t}' を同一opで require かつ produce（when ガード無し）");
				}
			}

			foreach(($flow['after'] ?? []) as $a){
				$ep = $a['endpoint'] ?? null;
				if($ep !== null && !isset($op_ids[$ep])){                    // G5
					$add('G5', $oid, "after endpoint '{$ep}' が operationId として解決できない");
				}
			}
		}

		$spec['x-flow-registry'] = $tokens;
		if(!empty($issues)){
			$spec['x-flow-issues'] = $issues;
		}
	}

	/**
	 * operation のリクエスト側（parameters / requestBody schema properties）に指定名のフィールドがあるか。
	 * location プレフィックス対応: bind に "header:X" / "cookie:X" / "query:X" / "path:X" / "body:X" を書ける。
	 * header / cookie は #[Parameter] ではなく security scheme / middleware（Bearer 等）由来のことが多いため、
	 * 明示宣言された in:header/cookie パラメータがあればそれを検証し、無ければ許容する。
	 */
	private function flow_has_request_field(array $op, string $name, array $schemas): bool{
		$in = null;
		if(preg_match('/^(header|cookie|query|path|body):(.+)$/', $name, $mch)){
			$in = $mch[1];
			$name = $mch[2];
		}
		if($in === 'header' || $in === 'cookie'){
			$declared = false;
			foreach(($op['parameters'] ?? []) as $p){
				if(($p['in'] ?? null) === $in){
					$declared = true;
					if(($p['name'] ?? null) === $name){
						return true;
					}
				}
			}
			return !$declared; // 宣言が無ければ security/middleware 由来として許容
		}
		foreach(($op['parameters'] ?? []) as $p){
			if(($p['name'] ?? null) === $name){
				return true;
			}
		}
		return $this->flow_schema_has_property($op['requestBody']['content'] ?? [], $name, $schemas);
	}

	/**
	 * operation のレスポンス側（responses schema properties）に指定名のフィールドがあるか。
	 */
	private function flow_has_response_field(array $op, string $name, array $schemas): bool{
		foreach(($op['responses'] ?? []) as $res){
			if($this->flow_schema_has_property($res['content'] ?? [], $name, $schemas)){
				return true;
			}
		}
		return false;
	}

	/**
	 * content[*].schema（$ref解決含む）の properties に name があるか。
	 */
	private function flow_schema_has_property(array $content, string $name, array $schemas): bool{
		foreach($content as $media){
			$schema = $media['schema'] ?? null;
			if($this->flow_schema_props_contain($schema, $name, $schemas, 0)){
				return true;
			}
		}
		return false;
	}

	private function flow_schema_props_contain($schema, string $name, array $schemas, int $depth): bool{
		if(!is_array($schema) || $depth > 4){
			return false;
		}
		if(isset($schema['$ref']) && is_string($schema['$ref'])){
			$ref = str_replace('#/components/schemas/', '', $schema['$ref']);
			$resolved = $schemas[$ref] ?? null;
			return $this->flow_schema_props_contain($resolved, $name, $schemas, $depth + 1);
		}
		if(is_array($schema['properties'] ?? null)){
			if(isset($schema['properties'][$name])){
				return true;
			}
			// envelope（result ラッパ等）でネストしたフィールドも辿る
			foreach($schema['properties'] as $sub){
				if($this->flow_schema_props_contain($sub, $name, $schemas, $depth + 1)){
					return true;
				}
			}
		}
		foreach(['items','allOf','oneOf','anyOf'] as $k){
			if(isset($schema[$k])){
				$list = isset($schema[$k][0]) ? $schema[$k] : [$schema[$k]];
				foreach($list as $sub){
					if($this->flow_schema_props_contain($sub, $name, $schemas, $depth + 1)){
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * 生成時にスキップしたエンドポイント一覧（path / action / error）。
	 * @return array<int,array{path:string,action:string,error:string}>
	 */
	public function get_skipped(): array{
		return $this->skipped;
	}

	/**
	 * 配列内の$refを再帰的に収集
	 */
	private function collect_refs($data, array &$refs): void{
		if(!is_array($data)){
			return;
		}
		foreach($data as $key => $value){
			if($key === '$ref' && is_string($value)){
				$schema_name = str_replace('#/components/schemas/', '', $value);
				$refs[$schema_name] = true;
			}else if(is_array($value)){
				$this->collect_refs($value, $refs);
			}
		}
	}

	/**
	 * スキーマの依存を再帰的に解決
	 */
	private function resolve_transitive_refs(string $name, array &$schemas, array &$resolved): void{
		if(isset($resolved[$name])){
			return;
		}
		if(!isset($schemas[$name])){
			return;
		}
		$resolved[$name] = true;

		$child_refs = [];
		$this->collect_refs($schemas[$name], $child_refs);
		foreach($child_refs as $ref => $_){
			$this->resolve_transitive_refs($ref, $schemas, $resolved);
		}
	}

	/**
	 * @s2sエンドポイント（Webhook）一覧を取得
	 * generate_spec()を呼び出した後に使用する
	 */
	public function get_webhooks(): array{
		return $this->webhooks;
	}

	/**
	 * 全タグ一覧を取得（webhook含む）
	 * generate_spec()を呼び出した後に使用する
	 */
	public function get_all_tags(): array{
		return $this->all_tags;
	}

	/**
	 * URLパターンをOpenAPIパス形式に変換
	 */
	private function convert_to_openapi_path(string $url_pattern, array $param_names = []): string{
		// :param 形式を {param} 形式に変換
		$path = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '{$1}', $url_pattern);

		// 正規表現グループ (...) を {paramName} 形式に変換
		$idx = 0;
		$path = substr(preg_replace_callback(
			"/([^\\\\])(\(.*?[^\\\\]\))/",
			function($m) use ($param_names, &$idx){
				$name = $param_names[$idx] ?? 'param'.($idx + 1);
				$idx++;
				return $m[1].'{'.$name.'}';
			},
			' '.$path
		), 1);

		// 正規表現エスケープを解除 (\. → . など)
		$path = preg_replace('/\\\\(.)/', '$1', $path);

		// 先頭にスラッシュがない場合は追加
		if(empty($path) || $path[0] !== '/'){
			$path = '/' . $path;
		}

		return $path;
	}

	/**
	 * オペレーション（エンドポイント定義）を構築
	 */
	private function build_operation(array $m, ?\ebi\Dt\DocInfo $info, array &$schemas, bool &$has_security, array &$tags, array $name_to_path=[], string $http_method='get'): array{
		$operation = [];

		if(!empty($m['summary'])){
			$operation['summary'] = $m['summary'];
		}

		if(isset($info) && !empty($info->document())){
			$lines = explode(PHP_EOL, $info->document());
			// 2行目以降をdescriptionとして使用（1行目はsummary）
			if(count($lines) > 1){
				$desc = trim(implode(PHP_EOL, array_slice($lines, 1)));
				if(!empty($desc)){
					$operation['description'] = $desc;
				}
			}
		}

		$operation['operationId'] = $m['name'] ?? null;

		$deprecated_see = isset($info) ? $info->opt('deprecated_see') : null;
		if(empty($deprecated_see) && isset($m['deprecated_see'])){
			$deprecated_see = \ebi\Dt\SourceAnalyzer::classify_see($m['deprecated_see']);
		}

		if(!empty($deprecated_see)){
			$m['deprecated'] = true;
		}

		if($m['deprecated'] ?? false){
			$operation['deprecated'] = true;

			if(!empty($deprecated_see)){
				$operation['x-deprecated-see'] = $deprecated_see;
			}
		}

		// タグ（クラス名をname、クラスの説明をx-displayNameに設定）
		if(isset($m['class'])){
			$class_parts = explode('\\', $m['class']);
			$tag_name = end($class_parts);
			$operation['tags'] = [$tag_name];

			// タグ定義を収集（重複を防ぐ）
			if(!isset($tags[$tag_name])){
				$tag_def = ['name' => $tag_name];

				try{
					$class_info = \ebi\Dt\SourceAnalyzer::class_info($m['class']);
					if(!empty($class_info->document())){
						[$display_name] = explode(PHP_EOL, $class_info->document());
						if(!empty($display_name)){
							$tag_def['x-displayName'] = $display_name;
						}
					}
				}catch(\Exception $e){
					// クラス情報の取得に失敗した場合はスキップ
				}

				$tags[$tag_name] = $tag_def;
			}
		}

		// パラメータ
		$parameters = [];
		$added_params = [];
		$has_body = in_array($http_method, ['post', 'put', 'patch']);
		$body_properties = [];
		$body_required = [];
		$is_multipart = false; // ファイル(format:binary)を含む場合 multipart/form-data にする

		// URLパスパラメータ
		if(isset($info)){
			foreach($info->params() as $param){
				$built = $this->build_parameter($param, 'path');
				$is_deprecated_param = str_contains($param->summary() ?? '', '@deprecated');

				if($is_deprecated_param){
					$built['deprecated'] = true;
				}
				$parameters[] = $built;

				// deprecatedなパラメータはrequestBodyへの追加をブロックしない
				if(!$is_deprecated_param){
					$added_params[$param->name()] = true;
				}
			}
		}

		// #[Parameter]属性からパラメータを取得（AttributeReader経由）
		if(isset($m['class'], $m['method'])){
			$attr_params = \ebi\AttributeReader::get_method($m['class'], $m['method'], 'request', 'summary');
			if(!empty($attr_params)){
				foreach($attr_params as $name => $data){
					if(!isset($added_params[$name])){
						$param = new \ebi\Dt\ParamInfo(
							$name,
							$data['type'] ?? 'string',
							$data['summary'] ?? ''
						);
						$in = ($data['in'] ?? 'query');
						$has_items = ($data['type'] ?? null) === 'array' && !empty($data['items']);
						// ファイルアップロード（OpenAPI3: multipart/form-data + type:string format:binary）
						$is_binary = (($data['format'] ?? null) === 'binary') || (($data['type'] ?? null) === 'file');
						// 非推奨: #[Parameter(deprecated: true)] または summary内 @deprecated
						$is_deprecated_param = !empty($data['deprecated']) || str_contains($data['summary'] ?? '', '@deprecated');

						if($has_body && $in !== 'path'){
							if($is_binary){
								$body_properties[$name] = ['type' => 'string', 'format' => 'binary'];
								if(!empty($data['summary'])){
									$body_properties[$name]['description'] = $data['summary'];
								}
								$is_multipart = true;
							}else{
								$body_properties[$name] = $this->build_body_property($param);
								if($has_items){
									$body_properties[$name]['items'] = $this->get_schema_type($data['items'], $schemas);
								}
							}
							if($is_deprecated_param){
								$body_properties[$name]['deprecated'] = true;
							}
							if(!empty($data['require'])){
								$body_required[] = $name;
							}
						}else{
							$p = $this->build_parameter($param, $in);
							if($has_items){
								$p['schema'] = ['type' => 'array', 'items' => $this->get_schema_type($data['items'], $schemas)];
								if(!empty($param->summary())){
									$p['schema']['description'] = $param->summary();
								}
							}
							if(!empty($data['require'])){
								$p['required'] = true;
							}
							if($is_deprecated_param){
								$p['deprecated'] = true;
							}
							$parameters[] = $p;
						}
						$added_params[$name] = true;
					}
				}
			}
		}

		// @requestアノテーション（DocBlock）からパラメータを取得（後方互換）
		if(isset($info) && $info->has_opt('requests')){
			foreach($info->opt('requests') as $param){
				if(!isset($added_params[$param->name()])){
					if($has_body){
						$body_properties[$param->name()] = $this->build_body_property($param);
						if($param->opt('require')){
							$body_required[] = $param->name();
						}
					}else{
						$parameters[] = $this->build_parameter($param, 'query');
					}
					$added_params[$param->name()] = true;
				}
			}
		}

		// OA\Parameter属性からパラメータを追加
		if(isset($m['class'], $m['method'])){
			$oa_parameters = $this->get_oa_parameters($m['class'], $m['method'], $schemas);
			foreach($oa_parameters as $oa_param){
				if(!isset($added_params[$oa_param['name']])){
					$parameters[] = $oa_param;
					$added_params[$oa_param['name']] = true;
				}
			}
		}

		// do_loginの場合、authクラスのlogin_conditionメソッドからパラメータを取得
		if(($m['method'] ?? '') === 'do_login'){
			$auth_class = $m['auth'] ?? null;

			// set_auth_objectからauthクラスを検出
			if(empty($auth_class) && isset($m['class'])){
				try{
					$ref = new \ReflectionMethod($m['class'], '__construct');
					$src = \ebi\Dt\SourceAnalyzer::method_src($ref);

					if(preg_match('/set_auth_object\(\s*new\s+([\\\\\w]+)/', $src, $auth_match)){
						$auth_class = $auth_match[1];
					}
				}catch(\ReflectionException $e){
				}
			}

			if(!empty($auth_class)){
				// @requestアノテーション（DocBlock）
				try{
					$auth_info = \ebi\Dt\SourceAnalyzer::method_info($auth_class, 'login_condition', true, false);

					if($auth_info->has_opt('requests')){
						foreach($auth_info->opt('requests') as $param){
							if(!isset($added_params[$param->name()])){
								if($has_body){
									$body_properties[$param->name()] = $this->build_body_property($param);
									if($param->opt('require')){
										$body_required[] = $param->name();
									}
								}else{
									$parameters[] = $this->build_parameter($param, 'query');
								}
								$added_params[$param->name()] = true;
							}
						}
					}
				}catch(\Exception $e){
				}

				// #[Parameter]属性（AttributeReader経由）
				$attr_login_params = \ebi\AttributeReader::get_method($auth_class, 'login_condition', 'request', 'summary');
				if(!empty($attr_login_params)){
					foreach($attr_login_params as $name => $data){
						if(!isset($added_params[$name])){
							$param = new \ebi\Dt\ParamInfo(
								$name,
								$data['type'] ?? 'string',
								$data['summary'] ?? ''
							);
							$in = ($data['in'] ?? 'query');
							$has_items = ($data['type'] ?? null) === 'array' && !empty($data['items']);

							if($has_body && $in !== 'path'){
								$body_properties[$name] = $this->build_body_property($param);
								if($has_items){
									$body_properties[$name]['items'] = $this->get_schema_type($data['items'], $schemas);
								}
								if(!empty($data['require'])){
									$body_required[] = $name;
								}
							}else{
								$p = $this->build_parameter($param, $in);
								if($has_items){
									$p['schema'] = ['type' => 'array', 'items' => $this->get_schema_type($data['items'], $schemas)];
									if(!empty($param->summary())){
										$p['schema']['description'] = $param->summary();
									}
								}
								if(!empty($data['require'])){
									$p['required'] = true;
								}
								$parameters[] = $p;
							}
							$added_params[$name] = true;
						}
					}
				}
			}
		}

		// deprecatedのパラメータがrequestBodyにも存在する場合はparametersから除外
		if(!empty($body_properties)){
			$parameters = array_values(array_filter($parameters, function($p) use ($body_properties){
				if(isset($body_properties[$p['name']]) && ($p['deprecated'] ?? false)){
					return false;
				}
				return true;
			}));
		}

		if(!empty($parameters)){
			$operation['parameters'] = $parameters;
		}

		// requestBody（POST/PUT/PATCHの場合）
		if($has_body && !empty($body_properties)){
			$body_schema = [
				'type' => 'object',
				'properties' => $body_properties,
			];
			if(!empty($body_required)){
				$body_schema['required'] = $body_required;
			}
			// ファイル(format:binary)を含む場合は multipart/form-data
			$media_type = $is_multipart ? 'multipart/form-data' : 'application/json';
			$operation['requestBody'] = [
				'required' => true,
				'content' => [
					$media_type => [
						'schema' => $body_schema,
					],
				],
			];
		}

		// レスポンス
		$x_throws = [];
		$operation['responses'] = $this->build_responses($m, $info, $schemas, $x_throws);

		if(!empty($x_throws)){
			$operation['x-throws'] = $x_throws;
		}

		// ログイン要件のチェック（クラスのAttribute または メソッドの@login_required）
		// do_loginはログイン処理自体なのでsecurity対象外
		$is_login = false;
		if(($m['method'] ?? '') !== 'do_login'){
			if(isset($m['class'])){
				$login_anon = \ebi\AttributeReader::get_class($m['class'], 'login');
				if(!empty($login_anon)){
					$is_login = true;
				}
			}
			if(!$is_login && isset($info) && $info->opt('login')){
				$is_login = true;
			}
		}
		if($is_login){
			$has_security = true;
			$operation['security'] = [['sessionAuth' => []]];

			// 401レスポンスを追加（未認証時は他のエラー同様 {"error":[...]} を返すのでErrorスキーマのcontentを付ける）
			if(!isset($operation['responses']['401'])){
				$operation['responses']['401'] = [
					'description' => 'Unauthorized - Login required',
					'content' => [
						'application/json' => ['schema' => $this->error_schema_ref($schemas)],
					],
				];
			}
		}

		// @see リンク
		if(isset($info) && !empty($info->opt('see_list'))){
			$see_list = [];
			foreach($info->opt('see_list') as $key => $see){
				$see_list[] = $see;
			}
			if(!empty($see_list)){
				$operation['x-see'] = $see_list;
			}
		}

		// mode
		if(!empty($m['mode'])){
			$operation['x-mode'] = $m['mode'];
		}

		// flow token（前提/効果/順序）: #[Requires]/#[Produces]/#[After]。#[Login]があればsession.userを前提に自動付与。
		if(isset($m['class'], $m['method'])){
			// クラス階層に宣言された #[FlowToken]（生産者なしの ambient トークン語彙）を集約
			foreach((\ebi\AttributeReader::get_class($m['class'], 'flow_token') ?? []) as $ft){
				if(isset($ft['token']) && !isset($this->flow_token_decls[$ft['token']])){
					$this->flow_token_decls[$ft['token']] = array_filter([
						'kind' => $ft['kind'] ?? 'ambient',
						'summary' => $ft['summary'] ?? null,
						'ambient' => $ft['ambient'] ?? true,
					], fn($v) => $v !== null);
				}
			}

			$produces = \ebi\AttributeReader::get_method($m['class'], $m['method'], 'produces') ?? [];
			$requires = \ebi\AttributeReader::get_method($m['class'], $m['method'], 'requires') ?? [];
			$after    = \ebi\AttributeReader::get_method($m['class'], $m['method'], 'after') ?? [];

			// do_login 等の共有ハンドラは per-route の差別化子である auth プラグインの login_condition にも
			// flow を宣言できる（http_method / request params を login_condition から読むのと同じ流儀）。
			if(!empty($m['auth'])){
				$produces = array_merge($produces, \ebi\AttributeReader::get_method($m['auth'], 'login_condition', 'produces') ?? []);
				$requires = array_merge($requires, \ebi\AttributeReader::get_method($m['auth'], 'login_condition', 'requires') ?? []);
				$after    = array_merge($after,    \ebi\AttributeReader::get_method($m['auth'], 'login_condition', 'after') ?? []);
			}

			// #[Login] はメソッド階層(info)にもクラス階層にも付き得るため両方を見る
			$has_login = (isset($info) && !empty($info->opt('login')))
				|| (isset($m['class']) && !empty(\ebi\AttributeReader::get_class($m['class'], 'login')));
			if($has_login){
				array_unshift($requires, [
					'token' => 'session.user',
					'optional' => false,
					'auto' => true,
					'summary' => 'ログイン済みセッション',
				]);
			}
			$flow = array_filter([
				'requires' => $requires,
				'produces' => $produces,
				'after' => $after,
			], fn($v) => !empty($v));

			if(!empty($flow)){
				$operation['x-flow'] = $flow;
			}
		}

		// nullを除去
		$operation = array_filter($operation, function($v){
			return $v !== null;
		});

		return $operation;
	}

	/**
	 * パラメータを構築
	 */
	private function build_parameter(\ebi\Dt\ParamInfo $param, string $in): array{
		$parameter = [
			'name' => $param->name(),
			'in' => $in,
		];

		if(!empty($param->summary())){
			$parameter['description'] = $param->summary();
		}

		if($in === 'path'){
			$parameter['required'] = true;
		}

		$parameter['schema'] = $this->get_schema_type($param->type());

		return $parameter;
	}

	/**
	 * requestBodyプロパティを構築
	 */
	private function build_body_property(\ebi\Dt\ParamInfo $param): array{
		$prop_schema = $this->get_schema_type($param->type());

		if(!empty($param->summary())){
			$prop_schema['description'] = $param->summary();
		}

		return $prop_schema;
	}

	/**
	 * PHPの型をOpenAPIスキーマ型に変換
	 */
	private function get_schema_type(string $php_type, array &$schemas = []): array{
		$is_array = (strpos($php_type, '[]') !== false);
		$is_map = (strpos($php_type, '{}') !== false);

		// []や{}のサフィックスを除去（バックスラッシュは保持）
		$type = str_replace(['[]', '{}'], '', $php_type);

		// クラス型かどうかを判定（大文字を含む場合はクラス型）
		$is_class = (bool)preg_match('/[A-Z]/', $type);

		if($is_class && !empty($type)){
			$schema = $this->build_model_schema($type, $schemas);
		}else{
			$schema = match(strtolower($type)){
				'int', 'integer' => ['type' => 'integer'],
				'number', 'float', 'double' => ['type' => 'number'],
				'bool', 'boolean' => ['type' => 'boolean'],
				'string', 'text' => ['type' => 'string'],
				'serial' => ['type' => 'integer', 'format' => 'serial'],
				'email' => ['type' => 'string', 'format' => 'email'],
				'datetime' => ['type' => 'string', 'format' => 'date-time'],
				'date' => ['type' => 'string', 'format' => 'date'],
				'time' => ['type' => 'string', 'format' => 'time'],
				'intdate' => ['type' => 'integer', 'format' => 'intdate'],
				'alnum' => ['type' => 'string', 'format' => 'alnum'],
			'timestamp' => ['type' => 'integer', 'format' => 'unix-timestamp'],
				'array' => ['type' => 'array', 'items' => ['type' => 'string']],
				'mixed' => [],
				default => ['type' => 'object'],
			};
		}

		if($is_array){
			$schema = [
				'type' => 'array',
				'items' => $schema,
			];
		}else if($is_map){
			$schema = [
				'type' => 'object',
				'additionalProperties' => $schema,
			];
		}

		return $schema;
	}

	/**
	 * モデルクラスのスキーマを構築
	 */
	private function build_model_schema(string $class_name, array &$schemas): array{
		// クラス名を正規化（先頭にバックスラッシュを付ける）
		$normalized_class = ltrim($class_name, '\\');

		// スキーマ名（PHPの名前空間形式）
		$schema_name = '\\' . $normalized_class;

		// 既に構築済みの場合は$refを返す
		if(isset($schemas[$schema_name])){
			return ['$ref' => '#/components/schemas/' . $schema_name];
		}

		// クラスが存在するか確認（先頭にバックスラッシュを付けて確認）
		$full_class_name = '\\' . $normalized_class;
		if(!class_exists($full_class_name)){
			return ['type' => 'object'];
		}

		try{
			$class_info = \ebi\Dt\SourceAnalyzer::class_info($full_class_name);

			// プレースホルダーを設置（再帰参照を防ぐ）
			$schemas[$schema_name] = ['type' => 'object'];

			$properties = [];
			$join_tables = [];
			if($class_info->has_opt('properties')){
				foreach($class_info->opt('properties') as $prop){
					// expose=>false (hash=>false) のプロパティはスキップ
					if($prop->opt('hash') === false || $prop->opt('expose') === false){
						continue;
					}

					$prop_schema = $this->get_schema_type($prop->type(), $schemas);

					if(!empty($prop->summary())){
						$prop_schema['description'] = $prop->summary();
					}

					// format option (date-time など)
					if($prop->opt('format')){
						$prop_schema['format'] = $prop->opt('format');
					}

					// primary key
					if($prop->opt('primary')){
						$prop_schema['x-primary'] = true;
					}

					// auto increment
					if($prop->opt('auto')){
						$prop_schema['x-auto'] = true;
					}

					// auto_now_add (created_at等)
					if($prop->opt('auto_now_add')){
						$prop_schema['x-auto-now-add'] = true;
					}

					// auto_now (updated_at等)
					if($prop->opt('auto_now')){
						$prop_schema['x-auto-now'] = true;
					}

					// auto_code_add (code等)
					if($prop->opt('auto_code_add')){
						$prop_schema['x-auto-code'] = true;
					}

					$properties[$prop->name()] = $prop_schema;
				}

				// cond（外部結合テーブル）- @参照を解決するため2パスで処理
				$cond_map = [];
				foreach($class_info->opt('properties') as $prop){
					$cond = $prop->opt('cond');
					if(!empty($cond)){
						$cond_map[$prop->name()] = $cond;
					}
				}

				foreach($cond_map as $prop_name => $cond){
					// @参照を解決
					$resolved = $cond;
					if(str_starts_with($cond, '@')){
						$ref_name = substr($cond, 1);
						$resolved = $cond_map[$ref_name] ?? $cond;
					}

					if(preg_match('/\((.+)\)/', $resolved, $cond_match)){
						$cond_tables = [];
						foreach(explode(',', $cond_match[1]) as $cond_part){
							$parts = explode('.', $cond_part, 3);
							if(count($parts) >= 2){
								$cond_tables[] = $parts[0];
							}
						}
						if(!empty($cond_tables) && isset($properties[$prop_name])){
							$properties[$prop_name]['x-join'] = implode(', ', $cond_tables);
							$join_tables = array_merge($join_tables, $cond_tables);
						}
					}
				}
			}

			// IteratorAggregateを実装するクラスの場合、getIterator()のプロパティを展開
			if(empty($properties)){
				foreach(\ebi\Dt\SourceAnalyzer::iterator_properties($full_class_name) as $prop){
					$prop_schema = $this->get_schema_type($prop->type(), $schemas);
					if(!empty($prop->summary())){
						$prop_schema['description'] = $prop->summary();
					}
					$properties[$prop->name()] = $prop_schema;
				}
			}

			$model_schema = ['type' => 'object'];

			if(!empty($properties)){
				$model_schema['properties'] = $properties;
			}

			if(!empty($class_info->document())){
				$model_schema['description'] = $class_info->document();
			}

			// Daoクラスかどうかを示すカスタムプロパティ
			if(is_subclass_of($full_class_name, \ebi\Dao::class)){
				$model_schema['x-dao'] = true;

				$table_annotation = \ebi\AttributeReader::get_class($full_class_name, 'table');
				if(!empty($table_annotation['name'])){
					$model_schema['x-table'] = $table_annotation['name'];
				}else{
					// 親Daoクラスのテーブル名を探す（Dao.phpと同じロジック）
					$table_class = $full_class_name;
					$parent_class = get_parent_class($full_class_name);

					while(true){
						$ref = new \ReflectionClass($parent_class);
						if(\ebi\Dao::class === $parent_class || $ref->isAbstract()){
							break;
						}
						$table_class = $parent_class;
						$parent_class = get_parent_class($parent_class);
					}
					$model_schema['x-table'] = \ebi\Util::camel2snake($table_class);
				}

				// 外部結合テーブル一覧
				if(!empty($join_tables)){
					$model_schema['x-joins'] = array_values(array_unique($join_tables));
				}
			}

			$schemas[$schema_name] = $model_schema;

			return ['$ref' => '#/components/schemas/' . $schema_name];
		}catch(\Exception $e){
			// クラス情報の取得に失敗した場合は汎用オブジェクト型を返す
			unset($schemas[$schema_name]);
			return ['type' => 'object'];
		}
	}

	private const OA_PARAMETER = 'OpenApi\Attributes\Parameter';
	private const OA_RESPONSE = 'OpenApi\Attributes\Response';
	private const OA_JSON_CONTENT = 'OpenApi\Attributes\JsonContent';

	/**
	 * OA\Parameter属性からパラメータを取得
	 */
	private function get_oa_parameters(string $class, string $method, array &$schemas): array{
		if(!class_exists(self::OA_PARAMETER)){
			return [];
		}

		$parameters = [];
		$r = new \ReflectionMethod($class, $method);
		$attrs = $r->getAttributes(self::OA_PARAMETER);

		foreach($attrs as $attr){
			$inst = $attr->newInstance();
			$param = [
				'name' => $inst->name,
				'in' => $inst->in ?? 'query',
			];

			if(!empty($inst->description)){
				$param['description'] = $inst->description;
			}
			if($inst->required ?? false){
				$param['required'] = true;
			}
			if($inst->deprecated ?? false){
				$param['deprecated'] = true;
			}
			if(isset($inst->schema)){
				$param['schema'] = $this->convert_oa_schema($inst->schema, $schemas);
			}else{
				$param['schema'] = ['type' => 'string'];
			}

			$parameters[] = $param;
		}

		return $parameters;
	}

	/**
	 * OA\Response属性からレスポンスを取得
	 */
	private function get_oa_responses(string $class, string $method, array &$schemas): array{
		if(!class_exists(self::OA_RESPONSE)){
			return [];
		}

		$responses = [];
		$r = new \ReflectionMethod($class, $method);
		$attrs = $r->getAttributes(self::OA_RESPONSE);

		foreach($attrs as $attr){
			$inst = $attr->newInstance();
			$status = (string)($inst->response ?? '200');
			$response = [
				'description' => $inst->description ?? '',
			];

			// JsonContentがある場合
			if(isset($inst->content) && is_array($inst->content)){
				foreach($inst->content as $content){
					if(is_a($content, self::OA_JSON_CONTENT)){
						$response['content'] = [
							'application/json' => [
								'schema' => $this->convert_oa_schema($content, $schemas),
							],
						];
						break;
					}
				}
			}

			$responses[$status] = $response;
		}

		return $responses;
	}

	/**
	 * OA\Schemaをスキーマ配列に変換
	 */
	private function convert_oa_schema(object $schema, array &$schemas): array{
		$result = [];

		if(isset($schema->ref) && !empty($schema->ref)){
			return ['$ref' => $schema->ref];
		}

		if(isset($schema->type)){
			$result['type'] = $schema->type;
		}

		if(isset($schema->format)){
			$result['format'] = $schema->format;
		}

		if(isset($schema->items)){
			$result['items'] = $this->convert_oa_schema($schema->items, $schemas);
		}

		if(isset($schema->properties) && is_array($schema->properties)){
			$result['properties'] = [];
			foreach($schema->properties as $prop){
				if(isset($prop->property)){
					$result['properties'][$prop->property] = $this->convert_oa_schema($prop, $schemas);
				}
			}
		}

		return empty($result) ? ['type' => 'object'] : $result;
	}

	/**
	 * 例外クラスからHTTPステータスコードを決定する。
	 * App の実挙動（\ebi\App）に合わせる:
	 *   \ebi\Exception を継承し http_status が非nullならその値、そうでなければ error_http_status（既定500）。
	 * http_status は protected プロパティなので、インスタンス化せず ReflectionClass::getDefaultProperties() で読む。
	 */
	private function exception_to_status(string $exception_name): int{
		$class = '\\'.ltrim($exception_name, '\\');

		if(strlen($class) > 1 && class_exists($class) && is_subclass_of($class, \ebi\Exception::class)){
			try{
				$defaults = (new \ReflectionClass($class))->getDefaultProperties();
				if(isset($defaults['http_status']) && $defaults['http_status'] !== null){
					return (int)$defaults['http_status'];
				}
			}catch(\ReflectionException $e){
			}
		}
		// \ebi\Exception を継承しない、または http_status 未設定 → App の error_http_status に合わせる
		return (int)\ebi\Conf::get('ebi\App@error_http_status', 500);
	}

	/**
	 * レスポンス定義を構築
	 */
	private function build_responses(array $m, ?\ebi\Dt\DocInfo $info, array &$schemas, array &$x_throws=[]): array{
		$responses = [];

		// OA\Response属性からレスポンスを取得（優先）
		if(isset($m['class'], $m['method'])){
			$oa_responses = $this->get_oa_responses($m['class'], $m['method'], $schemas);
			if(!empty($oa_responses)){
				return $oa_responses;
			}
		}

		// 成功レスポンス
		$success_response = [
			'description' => 'Successful response',
		];

		$properties = [];
		$added_props = [];

		// #[Response]属性からレスポンススキーマを構築（AttributeReader経由）
		if(isset($m['class'], $m['method'])){
			$attr_contexts = \ebi\AttributeReader::get_method($m['class'], $m['method'], 'context', 'summary');
			if(!empty($attr_contexts)){
				foreach($attr_contexts as $name => $data){
					$prop_schema = $this->get_schema_type($data['type'] ?? 'string', $schemas);

					if($data['type'] === 'array' && !empty($data['items'])){
						$prop_schema = ['type' => 'array', 'items' => $this->get_schema_type($data['items'], $schemas)];
					}else if(($data['attr'] ?? null) === 'a'){
						$prop_schema = ['type' => 'array', 'items' => $prop_schema];
					}else if(($data['attr'] ?? null) === 'h'){
						$prop_schema = ['type' => 'object', 'additionalProperties' => $prop_schema];
					}

					$summary = $data['summary'] ?? '';
					$is_deprecated = !empty($data['deprecated']);
					if(preg_match('/@deprecated/', $summary)){
						$is_deprecated = true;
						$summary = trim(preg_replace('/@deprecated.*/', '', $summary));
					}

					if(!empty($summary)){
						if(isset($prop_schema['$ref'])){
							$prop_schema = [
								'allOf' => [$prop_schema],
								'description' => $summary,
							];
						}else{
							$prop_schema['description'] = $summary;
						}
					}

					if($is_deprecated){
						$prop_schema['deprecated'] = true;
					}

					$properties[$name] = $prop_schema;
					$added_props[$name] = true;
				}
			}
		}

		// @contextアノテーション（DocBlock）からレスポンススキーマを構築（後方互換）
		$context_list = [];
		if(isset($info) && $info->has_opt('contexts')){
			$context_list = $info->opt('contexts');
		}

		// do_loginの場合、authクラスのlogin_condition・get_after_vars_loginの@contextをマージ
		if(($m['method'] ?? '') === 'do_login'){
			$auth_class = $m['auth'] ?? null;
			if(empty($auth_class) && isset($m['class'])){
				try{
					$ref = new \ReflectionMethod($m['class'], '__construct');
					$src = \ebi\Dt\SourceAnalyzer::method_src($ref);
					if(preg_match('/set_auth_object\s*\(\s*new\s+([\w\\\\]+)/',$src,$am)){
						$auth_class = $am[1];
					}
				}catch(\ReflectionException $e){
				}
			}
			if(!empty($auth_class)){
				foreach(['login_condition', 'get_after_vars_login'] as $auth_method){
					// @contextアノテーション（DocBlock）
					try{
						$auth_login_info = \ebi\Dt\SourceAnalyzer::method_info($auth_class, $auth_method, true, false);
						if($auth_login_info->has_opt('contexts')){
							$context_list = array_merge($context_list, $auth_login_info->opt('contexts'));
						}
					}catch(\Exception $e){
					}

					// #[Response]属性（AttributeReader経由）
					$attr_auth_contexts = \ebi\AttributeReader::get_method($auth_class, $auth_method, 'context', 'summary');
					if(!empty($attr_auth_contexts)){
						foreach($attr_auth_contexts as $name => $data){
							if(!isset($added_props[$name])){
								$prop_schema = $this->get_schema_type($data['type'] ?? 'string', $schemas);

								if(($data['type'] ?? null) === 'array' && !empty($data['items'])){
									$prop_schema = ['type' => 'array', 'items' => $this->get_schema_type($data['items'], $schemas)];
								}else if(($data['attr'] ?? null) === 'a'){
									$prop_schema = ['type' => 'array', 'items' => $prop_schema];
								}else if(($data['attr'] ?? null) === 'h'){
									$prop_schema = ['type' => 'object', 'additionalProperties' => $prop_schema];
								}

								$summary = $data['summary'] ?? '';
								$is_deprecated = false;
								if(preg_match('/@deprecated/', $summary)){
									$is_deprecated = true;
									$summary = trim(preg_replace('/@deprecated.*/', '', $summary));
								}

								if(!empty($summary)){
									if(isset($prop_schema['$ref'])){
										$prop_schema = [
											'allOf' => [$prop_schema],
											'description' => $summary,
										];
									}else{
										$prop_schema['description'] = $summary;
									}
								}

								if($is_deprecated){
									$prop_schema['deprecated'] = true;
								}

								$properties[$name] = $prop_schema;
								$added_props[$name] = true;
							}
						}
					}
				}
			}
		}

		foreach($context_list as $context){
			if(!isset($added_props[$context->name()])){
				$prop_schema = $this->get_schema_type($context->type(), $schemas);

				if(!empty($context->summary())){
					if(isset($prop_schema['$ref'])){
						$prop_schema = [
							'allOf' => [$prop_schema],
							'description' => $context->summary(),
						];
					}else{
						$prop_schema['description'] = $context->summary();
					}
				}

				if($context->opt('deprecated')){
					$prop_schema['deprecated'] = true;
				}

				$properties[$context->name()] = $prop_schema;
			}
		}

		if(!empty($properties)){
			$schema = [
				'type' => 'object',
				'properties' => $properties,
			];

			if($this->envelope){
				$schema = [
					'type' => 'object',
					'properties' => [
						'result' => $schema,
					],
				];
			}

			$success_response['content'] = [
				'application/json' => [
					'schema' => $schema,
				],
			];
		}

		$responses['200'] = $success_response;

		// #[ErrorResponse]属性からエラーレスポンスを取得
		if(isset($m['class'], $m['method'])){
			$attr_errors = \ebi\AttributeReader::get_method($m['class'], $m['method'], 'error_response');
			if(!empty($attr_errors)){
				foreach($attr_errors as $err){
					$status = (string)$err['status'];

					if(isset($responses[$status])){
						$responses[$status]['description'] .= "\n".$err['description'];
					}else{
						$responses[$status] = ['description' => $err['description']];
					}

					// x-throwsにも収集（UI側でenvelope時の表示切替に使用）
					if($status !== '401'){
						$x_throws[] = [
							'status' => (int)$status,
							'description' => $err['description'],
						];
					}
				}
			}
		}

		// #[Throws]属性からエラーレスポンスを取得
		if(isset($m['class'], $m['method'])){
			$attr_throws = \ebi\AttributeReader::get_method($m['class'], $m['method'], 'attr_throws');
			if(!empty($attr_throws)){
				foreach($attr_throws as $t){
					$exception_name = $t['exception'];
					$short_name = (($pos = strrpos($exception_name, '\\')) !== false) ? substr($exception_name, $pos + 1) : $exception_name;
					$status = (string)$this->exception_to_status($exception_name);

					$desc = trim($t['summary']);
					$label = empty($desc) ? $short_name : $short_name.' - '.$desc;

					if(isset($responses[$status])){
						$responses[$status]['description'] .= "\n".$label;
					}else{
						$responses[$status] = ['description' => $label];
					}

					// x-throwsにも収集
					if($status !== '401'){
						$x_throws[] = [
							'status' => (int)$status,
							'exception' => $short_name,
							'description' => $desc,
						];
					}
				}
			}
		}

		// エラーレスポンス（@throws DocBlockから、明示的に記述されたもののみ）
		if(isset($info) && $info->has_opt('throws')){
			$error_groups = [];

			foreach($info->opt('throws') as $throw){
				// ソースコードのthrow new文から自動検出されたものは、openapi_auto_throwsがfalseのときのみスキップ
				if($throw->opt('auto') && !$this->auto_throws){
					continue;
				}
				$exception_name = $throw->name();
				$short_name = (($pos = strrpos($exception_name, '\\')) !== false) ? substr($exception_name, $pos + 1) : $exception_name;
				$status = $this->exception_to_status($exception_name);

				$desc = trim($throw->summary());
				$label = empty($desc) ? $short_name : $short_name.' - '.$desc;

				$error_groups[$status][] = $label;

				// x-throwsにも収集
				if($status !== 401){
					$x_throws[] = [
						'status' => $status,
						'exception' => $short_name,
						'description' => $desc,
					];
				}
			}

			foreach($error_groups as $status => $labels){
				$responses[(string)$status] = [
					'description' => implode("\n", $labels),
				];
			}
		}

		// HttpHeader::send_status(NNN) で直接返すステータスを文書化する（auto_throws時のみ）。
		// 例外経由ではないため body の型は不明 → ステータスの存在のみ記録し、Errorスキーマは付けない。
		$status_only = [];
		if($this->auto_throws && isset($info)){
			foreach(($info->opt('send_statuses') ?: []) as $code){
				if($code >= 400 && $code < 600 && !isset($responses[(string)$code])){
					$responses[(string)$code] = ['description' => 'HttpHeader::send_status() で返却（レスポンス本文は不定）'];
					$status_only[(string)$code] = true;
				}
			}
		}

		// エラーレスポンス(4xx/5xx)に共通のErrorスキーマをcontentとして付与する。
		// ebiの例外は envelope 有無に関わらず {"error":[{message,type,group}]} 形式で返る（\ebi\App）。
		// これによりクライアント生成・モック作成でエラーボディを型として扱える。
		// ただし send_status() 由来のものは body が不明なので content を付けない。
		foreach($responses as $status => $resp){
			if((int)$status >= 400 && !isset($resp['content']) && !isset($status_only[$status])){
				$responses[$status]['content'] = [
					'application/json' => ['schema' => $this->error_schema_ref($schemas)],
				];
			}
		}

		// envelopeモードでは例外由来のエラーは HTTP 200 で {"error":[...]} として返る（\ebi\App）。
		// 例外由来の4xx（send_status由来は実HTTPステータスなので除く）をレスポンスから外し、
		// 200スキーマを oneOf: [成功, Error] にして「200で成功またはエラーが返る」ことを正確に表す。
		if($this->envelope){
			$folded = false;
			foreach($responses as $status => $resp){
				if((int)$status >= 400 && !isset($status_only[$status])){
					unset($responses[$status]);
					$folded = true;
				}
			}
			if($folded){
				$err_ref = $this->error_schema_ref($schemas);
				$success = $responses['200']['content']['application/json']['schema'] ?? ['type' => 'object'];
				$schema = isset($success['oneOf'])
					? ['oneOf' => array_merge($success['oneOf'], [$err_ref])]
					: ['oneOf' => [$success, $err_ref]];
				$responses['200']['content'] = ['application/json' => ['schema' => $schema]];
			}
		}

		return $responses;
	}

	/**
	 * 共通のErrorスキーマを components.schemas に用意し、その $ref を返す。
	 * ebiのエラーボディ形式（\ebi\App が出力する {"error":[{message,type,group}]}）に対応する。
	 */
	private function error_schema_ref(array &$schemas): array{
		if(!isset($schemas['Error'])){
			$schemas['Error'] = [
				'type' => 'object',
				'description' => 'エラーレスポンス。ebiの例外は envelope 有無に関わらずこの形式で返る。',
				'properties' => [
					'error' => [
						'type' => 'array',
						'items' => [
							'type' => 'object',
							'properties' => [
								'message' => ['type' => 'string', 'description' => 'エラーメッセージ'],
								'type' => ['type' => 'string', 'description' => '例外クラス名（namespaceを除いたbasename）'],
								'group' => ['type' => 'string', 'description' => 'エラーグループ（対象フィールド名等。無い場合は省略）'],
							],
							'required' => ['message', 'type'],
						],
					],
				],
				'required' => ['error'],
			];
		}
		return ['$ref' => '#/components/schemas/Error'];
	}
}
