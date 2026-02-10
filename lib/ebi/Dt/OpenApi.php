<?php
namespace ebi\Dt;
/**
 * OpenAPI仕様を生成する
 */
class OpenApi extends \ebi\flow\Request{
	private string $entry;

	public function __construct(?string $entryfile=null){
		if(empty($entryfile)){
			$trace = debug_backtrace(false);
			krsort($trace);

			foreach($trace as $t){
				if(isset($t['class']) && $t['class'] == 'ebi\Flow'){
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

	public function generate_spec(bool $envelope=false, bool $include_dev=false): array{
		$this->envelope = $envelope;
		$this->webhooks = [];
		$map = \ebi\Flow::get_map($this->entry);
		$patterns = $map['patterns'];
		unset($map['patterns']);

		$req = new \ebi\Request();
		$target_version = (string)$req->in_vars('version');
		$file_version = date('Ymd', filemtime($this->entry));
		$self_class = get_class($this);

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
						$path = $this->convert_to_openapi_path($url_pattern);
						$operation = $this->build_operation($m, $info, $schemas, $has_security, $tags, $name_to_path);

						$this->webhooks[] = [
							'path' => $path,
							'method' => strtoupper($http_method),
							'op' => $operation,
						];
						continue;
					}

					$path = $this->convert_to_openapi_path($url_pattern);
					$operation = $this->build_operation($m, $info, $schemas, $has_security, $tags, $name_to_path);

					if(!isset($spec['paths'][$path])){
						$spec['paths'][$path] = [];
					}
					$spec['paths'][$path][$http_method] = $operation;

				}catch(\Exception $e){
					// エラーが発生した場合はスキップ
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

		// pathsから参照されているスキーマのみを含める
		$used_refs = [];
		$this->collect_refs($spec['paths'], $used_refs);
		$resolved = [];
		foreach($used_refs as $ref => $_){
			$this->resolve_transitive_refs($ref, $schemas, $resolved);
		}
		$filtered_schemas = [];
		foreach($schemas as $name => $schema){
			if(isset($resolved[$name])){
				$filtered_schemas[$name] = $schema;
			}
		}

		// スキーマ名でソート
		ksort($filtered_schemas);

		if(!empty($filtered_schemas)){
			$spec['components']['schemas'] = $filtered_schemas;
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

		return $spec;
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
	private function convert_to_openapi_path(string $url_pattern): string{
		// :param 形式を {param} 形式に変換
		$path = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '{$1}', $url_pattern);

		// 先頭にスラッシュがない場合は追加
		if(empty($path) || $path[0] !== '/'){
			$path = '/' . $path;
		}

		return $path;
	}

	/**
	 * オペレーション（エンドポイント定義）を構築
	 */
	private function build_operation(array $m, ?\ebi\Dt\DocInfo $info, array &$schemas, bool &$has_security, array &$tags, array $name_to_path=[]): array{
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

		// URLパスパラメータ
		if(isset($info)){
			foreach($info->params() as $param){
				$parameters[] = $this->build_parameter($param, 'path');
				$added_params[$param->name()] = true;
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
						$p = $this->build_parameter($param, $in);
						if(!empty($data['require'])){
							$p['required'] = true;
						}
						$parameters[] = $p;
						$added_params[$name] = true;
					}
				}
			}
		}

		// @requestアノテーション（DocBlock）からパラメータを取得（後方互換）
		if(isset($info) && $info->has_opt('requests')){
			foreach($info->opt('requests') as $param){
				if(!isset($added_params[$param->name()])){
					$in = 'query';
					$parameters[] = $this->build_parameter($param, $in);
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

		// authクラスのlogin_conditionメソッドからパラメータを取得
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
			try{
				$auth_info = \ebi\Dt\SourceAnalyzer::method_info($auth_class, 'login_condition', true, false);

				if($auth_info->has_opt('requests')){
					foreach($auth_info->opt('requests') as $param){
						if(!isset($added_params[$param->name()])){
							$parameters[] = $this->build_parameter($param, 'query');
							$added_params[$param->name()] = true;
						}
					}
				}
			}catch(\Exception $e){
			}
		}

		if(!empty($parameters)){
			$operation['parameters'] = $parameters;
		}

		// レスポンス
		$operation['responses'] = $this->build_responses($m, $info, $schemas);

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

			// 401レスポンスを追加
			if(!isset($operation['responses']['401'])){
				$operation['responses']['401'] = [
					'description' => 'Unauthorized - Login required',
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
				'float', 'double' => ['type' => 'number'],
				'bool', 'boolean' => ['type' => 'boolean'],
				'string', 'text' => ['type' => 'string'],
				'serial' => ['type' => 'integer', 'format' => 'serial'],
				'email' => ['type' => 'string', 'format' => 'email'],
				'datetime' => ['type' => 'string', 'format' => 'date-time'],
				'date' => ['type' => 'string', 'format' => 'date'],
				'time' => ['type' => 'string', 'format' => 'time'],
				'timestamp' => ['type' => 'integer', 'format' => 'unix-timestamp'],
				'array' => ['type' => 'array', 'items' => ['type' => 'string']],
				'mixed' => ['type' => 'object'],
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
					// hash=>false のプロパティはスキップ
					if($prop->opt('hash') === false){
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
	 * レスポンス定義を構築
	 */
	private function build_responses(array $m, ?\ebi\Dt\DocInfo $info, array &$schemas): array{
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

					if(($data['attr'] ?? null) === 'a'){
						$prop_schema = ['type' => 'array', 'items' => $prop_schema];
					}else if(($data['attr'] ?? null) === 'h'){
						$prop_schema = ['type' => 'object', 'additionalProperties' => $prop_schema];
					}

					if(!empty($data['summary'])){
						if(isset($prop_schema['$ref'])){
							$prop_schema = [
								'allOf' => [$prop_schema],
								'description' => $data['summary'],
							];
						}else{
							$prop_schema['description'] = $data['summary'];
						}
					}

					$properties[$name] = $prop_schema;
					$added_props[$name] = true;
				}
			}
		}

		// @contextアノテーション（DocBlock）からレスポンススキーマを構築（後方互換）
		if(isset($info) && $info->has_opt('contexts')){
			foreach($info->opt('contexts') as $context){
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

					$properties[$context->name()] = $prop_schema;
				}
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

		// エラーレスポンス（throws情報から）
		if(isset($info) && $info->has_opt('throws')){
			foreach($info->opt('throws') as $throw){
				$exception_name = $throw->name();

				if(strpos($exception_name, 'UnauthorizedException') !== false){
					$responses['401'] = [
						'description' => $throw->summary() ?: 'Unauthorized',
					];
				}
			}
		}

		return $responses;
	}
}
