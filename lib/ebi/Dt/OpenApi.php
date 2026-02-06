<?php
namespace ebi\Dt;
/**
 * OpenAPI仕様を生成する
 */
class OpenApi extends \ebi\flow\Request{
	private string $entry;
	private array $doc_cache = [];

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
	public function generate_spec(): array{
		$map = \ebi\Flow::get_map($this->entry);
		$patterns = $map['patterns'];
		unset($map['patterns']);

		$req = new \ebi\Request();
		$target_version = (string)$req->in_vars('version');
		$envelope = (bool)$req->in_vars('envelope', false);
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
					$method_doc = null;
					$http_method = 'get';

					if(isset($m['method'])){
						$method_doc = $this->parse_method_doc($m['class'], $m['method']);

						if(!isset($m['version']) && !empty($method_doc['version'])){
							$m['version'] = $method_doc['version'];
						}
						if(empty($m['summary']) && !empty($method_doc['summary'])){
							$m['summary'] = $method_doc['summary'];
						}
						if($m['deprecated'] || ($method_doc['deprecated'] ?? false)){
							$m['deprecated'] = true;
						}

						$http_method = strtolower($method_doc['http_method'] ?? 'get');
						if(empty(trim($http_method))){
							$http_method = 'get';
						}
					}

					if(!isset($m['version'])){
						$m['version'] = $file_version;
					}

					if(!empty($target_version) && $m['version'] != $target_version){
						continue;
					}

					$path = $this->convert_to_openapi_path($url_pattern);
					$operation = $this->build_operation($m, $method_doc, $schemas, $has_security, $tags, $envelope);

					if(!isset($spec['paths'][$path])){
						$spec['paths'][$path] = [];
					}
					$spec['paths'][$path][$http_method] = $operation;

				}catch(\Exception $e){
					// エラーが発生した場合はスキップ
				}
			}
		}

		if(!empty($tags)){
			$spec['tags'] = array_values($tags);
		}

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

		return $spec;
	}

	/**
	 * メソッドのDocBlockを解析
	 */
	private function parse_method_doc(string $class, string $method): array{
		$cache_key = $class . '::' . $method;
		if(isset($this->doc_cache[$cache_key])){
			return $this->doc_cache[$cache_key];
		}

		$result = [
			'summary' => '',
			'description' => '',
			'params' => [],
			'requests' => [],
			'contexts' => [],
			'throws' => [],
			'http_method' => 'get',
			'deprecated' => false,
			'version' => null,
		];

		try{
			$ref = new \ReflectionMethod($class, $method);
			$doc = $ref->getDocComment();

			if($doc !== false){
				$result = array_merge($result, $this->parse_docblock($doc));
			}

			// メソッドパラメータから@paramを補完
			foreach($ref->getParameters() as $param){
				$param_name = $param->getName();
				$exists = false;
				foreach($result['params'] as $p){
					if($p['name'] === $param_name){
						$exists = true;
						break;
					}
				}
				if(!$exists){
					$type = 'mixed';
					if($param->hasType()){
						$type = $param->getType()->getName();
					}
					$result['params'][] = [
						'name' => $param_name,
						'type' => $type,
						'summary' => '',
					];
				}
			}
		}catch(\ReflectionException $e){
			// リフレクション失敗時は空の結果を返す
		}

		$this->doc_cache[$cache_key] = $result;
		return $result;
	}

	/**
	 * クラスのDocBlockを解析
	 */
	private function parse_class_doc(string $class): array{
		$cache_key = 'class:' . $class;
		if(isset($this->doc_cache[$cache_key])){
			return $this->doc_cache[$cache_key];
		}

		$result = [
			'summary' => '',
			'description' => '',
			'properties' => [],
		];

		try{
			$ref = new \ReflectionClass($class);
			$doc = $ref->getDocComment();

			if($doc !== false){
				$parsed = $this->parse_docblock($doc);
				$result['summary'] = $parsed['summary'];
				$result['description'] = $parsed['description'];
			}

			// プロパティを取得
			foreach($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop){
				$prop_doc = $prop->getDocComment();
				$prop_info = [
					'name' => $prop->getName(),
					'type' => 'mixed',
					'summary' => '',
					'hash' => true,
				];

				if($prop->hasType()){
					$prop_info['type'] = $prop->getType()->getName();
				}

				if($prop_doc !== false){
					$prop_parsed = $this->parse_docblock($prop_doc);
					if(!empty($prop_parsed['summary'])){
						$prop_info['summary'] = $prop_parsed['summary'];
					}
					if(!empty($prop_parsed['var_type'])){
						$prop_info['type'] = $prop_parsed['var_type'];
					}
					if(isset($prop_parsed['hash'])){
						$prop_info['hash'] = $prop_parsed['hash'];
					}
				}

				$result['properties'][] = $prop_info;
			}
		}catch(\ReflectionException $e){
			// リフレクション失敗時は空の結果を返す
		}

		$this->doc_cache[$cache_key] = $result;
		return $result;
	}

	/**
	 * DocBlockをパース
	 */
	private function parse_docblock(string $doc): array{
		$result = [
			'summary' => '',
			'description' => '',
			'params' => [],
			'requests' => [],
			'contexts' => [],
			'throws' => [],
			'http_method' => 'get',
			'deprecated' => false,
			'version' => null,
			'var_type' => null,
			'hash' => null,
		];

		// DocBlockのコメント記号を除去
		$doc = preg_replace('/^[\s]*\*[\s]?/m', '', $doc);
		$doc = str_replace(['/**', '*/'], '', $doc);
		$doc = trim($doc);

		$lines = explode("\n", $doc);
		$desc_lines = [];
		$in_description = true;

		foreach($lines as $line){
			$line = trim($line);

			if(empty($line)){
				if($in_description && !empty($desc_lines)){
					$desc_lines[] = '';
				}
				continue;
			}

			// アノテーション行
			if($line[0] === '@'){
				$in_description = false;

				if(preg_match('/@param\s+(\S+)\s+\$(\w+)(?:\s+(.*))?/', $line, $m)){
					$result['params'][] = [
						'name' => $m[2],
						'type' => $m[1],
						'summary' => $m[3] ?? '',
					];
				}elseif(preg_match('/@request\s+(\S+)\s+\$(\w+)(?:\s+(.*))?/', $line, $m)){
					$result['requests'][] = [
						'name' => $m[2],
						'type' => $m[1],
						'summary' => $m[3] ?? '',
					];
				}elseif(preg_match('/@context\s+(\S+)\s+\$(\w+)(?:\s+(.*))?/', $line, $m)){
					$result['contexts'][] = [
						'name' => $m[2],
						'type' => $m[1],
						'summary' => $m[3] ?? '',
					];
				}elseif(preg_match('/@throws\s+(\S+)(?:\s+(.*))?/', $line, $m)){
					$result['throws'][] = [
						'name' => $m[1],
						'summary' => $m[2] ?? '',
					];
				}elseif(preg_match('/@http_method\s+(\S+)/', $line, $m)){
					$result['http_method'] = $m[1];
				}elseif(preg_match('/@version\s+(\S+)/', $line, $m)){
					$result['version'] = $m[1];
				}elseif(strpos($line, '@deprecated') === 0){
					$result['deprecated'] = true;
				}elseif(preg_match('/@var\s+(\S+)/', $line, $m)){
					$result['var_type'] = $m[1];
				}elseif(preg_match('/@hash\s+(true|false)/', $line, $m)){
					$result['hash'] = ($m[1] === 'true');
				}
			}else{
				if($in_description){
					$desc_lines[] = $line;
				}
			}
		}

		if(!empty($desc_lines)){
			$result['summary'] = array_shift($desc_lines);
			$result['description'] = trim(implode("\n", $desc_lines));
			if(empty($result['description'])){
				$result['description'] = $result['summary'];
			}
		}

		return $result;
	}

	/**
	 * URLパターンをOpenAPIパス形式に変換
	 */
	private function convert_to_openapi_path(string $url_pattern): string{
		$path = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '{$1}', $url_pattern);

		if(empty($path) || $path[0] !== '/'){
			$path = '/' . $path;
		}

		return $path;
	}

	/**
	 * オペレーション（エンドポイント定義）を構築
	 */
	private function build_operation(array $m, ?array $method_doc, array &$schemas, bool &$has_security, array &$tags, bool $envelope = false): array{
		$operation = [];

		if(!empty($m['summary'])){
			$operation['summary'] = $m['summary'];
		}

		if(isset($method_doc) && !empty($method_doc['description'])){
			$desc = $method_doc['description'];
			if($desc !== ($m['summary'] ?? '')){
				$operation['description'] = $desc;
			}
		}

		$operation['operationId'] = $m['name'] ?? null;

		if($m['deprecated'] ?? false){
			$operation['deprecated'] = true;
		}

		// タグ
		if(isset($m['class'])){
			$class_parts = explode('\\', $m['class']);
			$tag_name = end($class_parts);
			$operation['tags'] = [$tag_name];

			if(!isset($tags[$tag_name])){
				$tag_def = ['name' => $tag_name];

				try{
					$class_doc = $this->parse_class_doc($m['class']);
					if(!empty($class_doc['summary'])){
						$tag_def['x-displayName'] = $class_doc['summary'];
					}
				}catch(\Exception $e){
				}

				$tags[$tag_name] = $tag_def;
			}
		}

		// パラメータ
		$parameters = [];

		if(isset($method_doc)){
			foreach($method_doc['params'] as $param){
				$parameters[] = $this->build_parameter($param, 'path');
			}

			foreach($method_doc['requests'] as $param){
				$parameters[] = $this->build_parameter($param, 'query');
			}
		}

		if(!empty($parameters)){
			$operation['parameters'] = $parameters;
		}

		// レスポンス
		$operation['responses'] = $this->build_responses($m, $method_doc, $schemas, $envelope);

		// ログイン要件
		if(isset($m['class'])){
			$login_anon = \ebi\Annotation::get_class($m['class'], 'login');
			if(!empty($login_anon)){
				$has_security = true;
				$operation['security'] = [['sessionAuth' => []]];

				if(!isset($operation['responses']['401'])){
					$operation['responses']['401'] = [
						'description' => 'Unauthorized - Login required',
					];
				}
			}
		}

		return array_filter($operation, fn($v) => $v !== null);
	}

	/**
	 * パラメータを構築
	 */
	private function build_parameter(array $param, string $in): array{
		$parameter = [
			'name' => $param['name'],
			'in' => $in,
		];

		if(!empty($param['summary'])){
			$parameter['description'] = $param['summary'];
		}

		if($in === 'path'){
			$parameter['required'] = true;
		}

		$parameter['schema'] = $this->get_schema_type($param['type']);

		return $parameter;
	}

	/**
	 * PHPの型をOpenAPIスキーマ型に変換
	 */
	private function get_schema_type(string $php_type, array &$schemas = []): array{
		$is_array = (strpos($php_type, '[]') !== false);
		$is_map = (strpos($php_type, '{}') !== false);

		$type = str_replace(['[]', '{}'], '', $php_type);
		$is_class = (bool)preg_match('/[A-Z]/', $type);

		if($is_class && !empty($type)){
			$schema = $this->build_model_schema($type, $schemas);
		}else{
			$schema = match(strtolower($type)){
				'int', 'integer' => ['type' => 'integer'],
				'float', 'double' => ['type' => 'number'],
				'bool', 'boolean' => ['type' => 'boolean'],
				'string' => ['type' => 'string'],
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
		$normalized_class = ltrim($class_name, '\\');
		$schema_name = str_replace('\\', '.', $normalized_class);

		if(isset($schemas[$schema_name])){
			return ['$ref' => '#/components/schemas/' . $schema_name];
		}

		$full_class_name = '\\' . $normalized_class;
		if(!class_exists($full_class_name)){
			return ['type' => 'object'];
		}

		try{
			$class_doc = $this->parse_class_doc($full_class_name);

			// プレースホルダー
			$schemas[$schema_name] = ['type' => 'object'];

			$properties = [];
			foreach($class_doc['properties'] as $prop){
				if($prop['hash'] === false){
					continue;
				}

				$prop_schema = $this->get_schema_type($prop['type'], $schemas);

				if(!empty($prop['summary'])){
					$prop_schema['description'] = $prop['summary'];
				}

				$properties[$prop['name']] = $prop_schema;
			}

			$model_schema = ['type' => 'object'];

			if(!empty($properties)){
				$model_schema['properties'] = $properties;
			}

			if(!empty($class_doc['description'])){
				$model_schema['description'] = $class_doc['description'];
			}

			$schemas[$schema_name] = $model_schema;

			return ['$ref' => '#/components/schemas/' . $schema_name];
		}catch(\Exception $e){
			unset($schemas[$schema_name]);
			return ['type' => 'object'];
		}
	}

	/**
	 * レスポンス定義を構築
	 */
	private function build_responses(array $m, ?array $method_doc, array &$schemas, bool $envelope = false): array{
		$responses = [];

		$success_response = [
			'description' => 'Successful response',
		];

		if(isset($method_doc) && !empty($method_doc['contexts'])){
			$properties = [];

			foreach($method_doc['contexts'] as $context){
				$prop_schema = $this->get_schema_type($context['type'], $schemas);

				if(!empty($context['summary'])){
					if(isset($prop_schema['$ref'])){
						$prop_schema = [
							'allOf' => [$prop_schema],
							'description' => $context['summary'],
						];
					}else{
						$prop_schema['description'] = $context['summary'];
					}
				}

				$properties[$context['name']] = $prop_schema;
			}

			if(!empty($properties)){
				$response_schema = [
					'type' => 'object',
					'properties' => $properties,
				];

				if($envelope){
					$response_schema = [
						'type' => 'object',
						'properties' => [
							'result' => $response_schema,
						],
					];
				}

				$success_response['content'] = [
					'application/json' => [
						'schema' => $response_schema,
					],
				];
			}
		}

		$responses['200'] = $success_response;

		if(isset($method_doc) && !empty($method_doc['throws'])){
			foreach($method_doc['throws'] as $throw){
				if(strpos($throw['name'], 'UnauthorizedException') !== false){
					$responses['401'] = [
						'description' => $throw['summary'] ?: 'Unauthorized',
					];
				}
			}
		}

		return $responses;
	}
}
