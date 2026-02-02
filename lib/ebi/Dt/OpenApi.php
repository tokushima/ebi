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
	public function generate_spec(): array{
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
						$info = \ebi\Dt\Man::method_info($m['class'], $m['method'], true, true);

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

						$http_method = strtolower($info->opt('http_method') ?? 'get');
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
					$operation = $this->build_operation($m, $info, $schemas, $has_security);

					if(!isset($spec['paths'][$path])){
						$spec['paths'][$path] = [];
					}
					$spec['paths'][$path][$http_method] = $operation;

				}catch(\Exception $e){
					// エラーが発生した場合はスキップ
				}
			}
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
	private function build_operation(array $m, ?\ebi\Dt\DocInfo $info, array &$schemas, bool &$has_security): array{
		$operation = [];

		if(!empty($m['summary'])){
			$operation['summary'] = $m['summary'];
		}

		if(isset($info) && !empty($info->document())){
			$desc = $info->document();
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
			$tag = end($class_parts);
			$operation['tags'] = [$tag];
		}

		// パラメータ
		$parameters = [];

		// URLパスパラメータ
		if(isset($info)){
			foreach($info->params() as $param){
				$parameters[] = $this->build_parameter($param, 'path');
			}
		}

		// リクエストパラメータ
		if(isset($info) && $info->has_opt('requests')){
			foreach($info->opt('requests') as $param){
				$in = (strtolower($info->opt('http_method') ?? 'get') === 'post') ? 'query' : 'query';
				$parameters[] = $this->build_parameter($param, $in);
			}
		}

		if(!empty($parameters)){
			$operation['parameters'] = $parameters;
		}

		// レスポンス
		$operation['responses'] = $this->build_responses($m, $info, $schemas);

		// ログイン要件のチェック
		if(isset($m['class'])){
			$login_anon = \ebi\Annotation::get_class($m['class'], 'login');
			if(!empty($login_anon)){
				$has_security = true;
				$operation['security'] = [['sessionAuth' => []]];

				// 401レスポンスを追加
				if(!isset($operation['responses']['401'])){
					$operation['responses']['401'] = [
						'description' => 'Unauthorized - Login required',
					];
				}
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
	private function build_parameter(\ebi\Dt\DocParam $param, string $in): array{
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
		// クラス名を正規化
		$normalized_class = ltrim($class_name, '\\');

		// スキーマ名（バックスラッシュをドットに変換）
		$schema_name = str_replace('\\', '.', $normalized_class);

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
			$class_info = \ebi\Dt\Man::class_info($full_class_name);

			// プレースホルダーを設置（再帰参照を防ぐ）
			$schemas[$schema_name] = ['type' => 'object'];

			$properties = [];
			if($class_info->has_opt('properties')){
				foreach($class_info->opt('properties') as $prop){
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

			$schemas[$schema_name] = $model_schema;

			return ['$ref' => '#/components/schemas/' . $schema_name];
		}catch(\Exception $e){
			// クラス情報の取得に失敗した場合は汎用オブジェクト型を返す
			unset($schemas[$schema_name]);
			return ['type' => 'object'];
		}
	}

	/**
	 * レスポンス定義を構築
	 */
	private function build_responses(array $m, ?\ebi\Dt\DocInfo $info, array &$schemas): array{
		$responses = [];

		// 成功レスポンス
		$success_response = [
			'description' => 'Successful response',
		];

		// contextsからレスポンススキーマを構築
		if(isset($info) && $info->has_opt('contexts')){
			$properties = [];

			foreach($info->opt('contexts') as $context){
				$prop_schema = $this->get_schema_type($context->type(), $schemas);

				// $refを使用している場合、descriptionはallOfでラップする必要がある
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

			if(!empty($properties)){
				$success_response['content'] = [
					'application/json' => [
						'schema' => [
							'type' => 'object',
							'properties' => $properties,
						],
					],
				];
			}
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
