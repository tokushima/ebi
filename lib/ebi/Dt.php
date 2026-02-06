<?php
namespace ebi;

use ebi\Attribute\Automap;
use ebi\Attribute\Parameter;

/**
 * Developer Tools - OpenAPI-based API Documentation & Development Support
 */
class Dt extends \ebi\flow\Request{
	private string $entry;
	private static array $mock = [];

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
			$this->entry = realpath($entryfile);
		}
		parent::__construct();
	}

	/**
	 * Developer Tools Endpoints
	 */
	#[Automap]
	public function index(): void{
		$this->render_react_app();
	}

	/**
	 * OpenAPI Specification (JSON)
	 */
	#[Automap(suffix: '.json')]
	public function openapi(): void{
		$spec = (new \ebi\Dt\OpenApi($this->entry))->generate_spec();

		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');
		\ebi\HttpHeader::send('Access-Control-Allow-Origin', '*');
		echo json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	/**
	 * Redoc API Documentation
	 */
	#[Automap]
	public function redoc(): void{
		$spec = (new \ebi\Dt\OpenApi($this->entry))->generate_spec();
		$title = htmlspecialchars($spec['info']['title'] ?? 'API Documentation', ENT_QUOTES, 'UTF-8');
		$spec_json = json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		echo <<<HTML
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{$title} - Redoc</title>
	<link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700|Roboto:300,400,700" rel="stylesheet">
	<style>body { margin: 0; padding: 0; }</style>
</head>
<body>
	<div id="redoc-container"></div>
	<script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
	<script>
		const spec = {$spec_json};
		Redoc.init(spec, { hideDownloadButton: true }, document.getElementById('redoc-container'));
	</script>
</body>
</html>
HTML;
		exit;
	}

	/**
	 * Sent Mails API (SmtpBlackholeDao)
	 */
	#[Automap(suffix: '.json')]
	public function sent_mails(): void{
		$mails = [];

		try{
			$count = 0;
			foreach(\ebi\SmtpBlackholeDao::find_all(Q::order('-id')) as $mail){
				$mails[] = [
					'id' => $mail->id(),
					'from' => $mail->from(),
					'to' => trim($mail->to()),
					'subject' => $mail->subject(),
					'message' => $mail->message(),
					'tcode' => $mail->tcode(),
					'create_date' => date('Y-m-d H:i:s', $mail->create_date()),
				];
				if(++$count >= 50) break;
			}
		}catch(\Exception){
			// テーブルが存在しない場合など
		}

		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');
		echo json_encode(['mails' => $mails], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * Configs API - List all Conf::get/gets definitions
	 */
	#[Automap(suffix: '.json')]
	public function configs(): void{
		$configs = [];

		foreach(self::classes() as $class_info){
			try{
				$info = \ebi\Dt\SourceAnalyzer::class_info($class_info['class']);
				$config_list = $info->opt('config_list', []);

				foreach($config_list as $name => $conf_info){
					$params = [];
					foreach($conf_info->params() as $p){
						$params[] = [
							'name' => $p->name(),
							'type' => $p->type(),
							'summary' => $p->summary(),
						];
					}
					$configs[] = [
						'class' => $info->name(),
						'name' => $name,
						'summary' => $conf_info->summary(),
						'params' => $params,
						'defined' => $conf_info->opt('def', false),
					];
				}
			}catch(\Exception){
			}
		}

		usort($configs, fn($a, $b) => strcmp($a['class'].'@'.$a['name'], $b['class'].'@'.$b['name']));

		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');
		echo json_encode(['configs' => $configs], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit;
	}

	// === Render Methods ===

	private function render_react_app(): void{
		$spec = (new \ebi\Dt\OpenApi($this->entry))->generate_spec();
		$spec_json = json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		$mail_templates = [];
		foreach(\ebi\Dt\SourceAnalyzer::mail_template_list() as $info){
			$mail_templates[] = [
				'name' => $info->name(),
				'code' => $info->opt('x_t_code'),
				'subject' => $info->opt('subject') ?? '',
				'summary' => $info->document(),
			];
		}
		$mail_json = json_encode($mail_templates, JSON_UNESCAPED_UNICODE);

		// FlowHelperでURLを生成
		$helper = new \ebi\FlowHelper();
		$urls = json_encode([
			'openapi' => $helper->package_method_url('openapi'),
			'redoc' => $helper->package_method_url('redoc'),
			'sent_mails' => $helper->package_method_url('sent_mails'),
			'configs' => $helper->package_method_url('configs'),
		], JSON_UNESCAPED_SLASHES);

		// ビルド済みのapp.jsを読み込む
		$app_js = file_get_contents(__DIR__.'/Dt/assets/app.js');

		echo <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Developer Tools</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		.method-badge { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.25rem 0.5rem; border-radius: 0.25rem; color: white; }
		.method-get { background-color: #0d6efd; }
		.method-post { background-color: #198754; }
		.method-put { background-color: #fd7e14; }
		.method-delete { background-color: #dc3545; }
		.method-patch { background-color: #20c997; }
		.endpoint-row { cursor: pointer; transition: background-color 0.15s; }
		.endpoint-row:hover { background-color: rgba(0,0,0,0.03); }
		.code-block { background-color: #1e1e1e; color: #4ec9b0; font-family: monospace; font-size: 0.875rem; max-height: 400px; overflow: auto; }
		pre { white-space: pre-wrap; word-break: break-all; }
	</style>
</head>
<body class="bg-light">
	<div id="root"></div>
	<script>
		window.spec = {$spec_json};
		window.mailTemplates = {$mail_json};
		window.apiUrls = {$urls};
	</script>
	<script>{$app_js}</script>
</body>
</html>
HTML;
		exit;
	}

	private function get_schema_type(string $class): string{
		if(is_subclass_of($class, \ebi\Dao::class)) return 'model';
		if(is_subclass_of($class, \ebi\flow\Request::class)) return 'request';
		return 'other';
	}

	// === Static Utilities ===

	public static function classes(?string $parent_class=null): \Generator{
		$include_path = [];

		if(is_dir(getcwd().DIRECTORY_SEPARATOR.'lib')){
			$include_path[] = realpath(getcwd().DIRECTORY_SEPARATOR.'lib');
		}
		if(class_exists('Composer\Autoload\ClassLoader')){
			$r = new \ReflectionClass('Composer\Autoload\ClassLoader');
			$vendor_dir = dirname(dirname($r->getFileName()));

			if(is_file($loader_php = $vendor_dir.DIRECTORY_SEPARATOR.'autoload.php')){
				$loader = include($loader_php);

				foreach(array_merge($loader->getPrefixes(), $loader->getPrefixesPsr4()) as $ns){
					foreach($ns as $path){
						$path = realpath($path);
						if($path && strpos($path, $vendor_dir) === false){
							$include_path[] = $path;
						}
					}
				}
			}
		}
		$include_path = array_unique($include_path);

		$load_class_file = function($f){
			if(strpos($f->getPathname(), DIRECTORY_SEPARATOR.'.') === false
				&& strpos($f->getPathname(), DIRECTORY_SEPARATOR.'_') === false
				&& strpos($f->getPathname(), DIRECTORY_SEPARATOR.'cmd'.DIRECTORY_SEPARATOR) === false
				&& ctype_upper(substr($f->getFilename(), 0, 1))
				&& substr($f->getFilename(), -4) === '.php'
			){
				try{ include_once($f->getPathname()); }catch(\Exception){}
			}
		};

		foreach($include_path as $libdir){
			if($libdir !== '.' && is_dir($libdir)){
				foreach(\ebi\Util::ls($libdir, true) as $f){
					$load_class_file($f);
				}
			}
		}

		$use_vendor = \ebi\Conf::gets('use_vendor');
		$use_vendor_callback = \ebi\Conf::get('use_vendor_callback');

		if(!empty($use_vendor_callback)){
			$callback_result = call_user_func($use_vendor_callback);
			if(is_array($callback_result)){
				$use_vendor = array_merge($use_vendor, $callback_result);
			}
		}

		foreach($use_vendor as $class){
			$find_package = (substr($class, -1) === '*');
			if($find_package) $class = substr($class, 0, -1);
			if(class_exists($class) && $find_package){
				$r = new \ReflectionClass($class);
				foreach(\ebi\Util::ls(dirname($r->getFileName()), true) as $f){
					$load_class_file($f);
				}
			}
		}

		foreach(get_declared_classes() as $class){
			$r = new \ReflectionClass($class);
			if(!$r->isInterface()
				&& (empty($parent_class) || is_subclass_of($r->getName(), $parent_class))
				&& $r->getFileName() !== false
				&& strpos($r->getName(), '_') === false
				&& strpos($r->getName(), 'Composer') === false
				&& strpos($r->getName(), 'cmdman') === false
				&& strpos($r->getName(), 'testman') === false
			){
				yield ['filename' => $r->getFileName(), 'class' => '\\'.$r->getName()];
			}
		}
	}

	public static function add_mock(mixed ...$mock_class_names): void{
		foreach($mock_class_names as $class_name){
			if(is_object($class_name)) $class_name = get_class($class_name);
			if(is_string($class_name)){
				if(!(class_exists($class_name) && is_subclass_of($class_name, \ebi\Dt\MockRequest::class))){
					throw new \InvalidArgumentException('Invalid mock class: '.$class_name);
				}
				self::$mock[] = ltrim($class_name, '\\');
			}else if(is_array($class_name)){
				self::add_mock(...$class_name);
			}
		}
	}

	public static function mock_flow_mappings(array $map=[]): array{
		$patterns = $map['patterns'] ?? [];
		$patterns[''] = ['action' => 'ebi\Dt', 'mode' => '@dev'];
		foreach(self::$mock as $class_name){
			$patterns[str_replace('\\', '/', $class_name)] = ['action' => $class_name, 'mode' => '@dev'];
		}
		$map['patterns'] = $patterns;
		return $map;
	}

	public static function url_rewrite(string $url): string{
		if(\ebi\Conf::is_production()) return $url;

		$rewrite = self::get_url_rewrite();
		if(empty($rewrite)) return $url;

		[$base_url, $query] = (strpos($url, '?') === false) ? [$url, ''] : explode('?', $url, 2);

		foreach($rewrite as $pattern => $replacement){
			$subject = (strpos($pattern, '\?') === false) ? $base_url : $url;
			if(!empty($pattern) && preg_match($pattern, $subject, $matches)){
				$new_url_params = [];
				if(preg_match_all('/(\/%[0-9s]+)/', $replacement, $param_matches)){
					$match_params = array_slice($matches, 1);
					foreach($param_matches[0] as $i => $param_match){
						$idx = ($param_match === 's') ? $i : (int)substr($param_match, 2);
						$new_url_params[$idx] = $match_params[$idx] ?? '';
						$replacement = str_replace($param_match, '', $replacement);
					}
				}
				$new_url = preg_replace($pattern, $replacement, $subject);
				if(strpos($new_url, '?') !== false){
					[$new_url, $new_query] = explode('?', $new_url, 2);
					$query = $query.(empty($query) ? '' : '&').$new_query;
				}
				$new_url = self::url(empty($new_url_params) ? $new_url : array_merge([$new_url], $new_url_params));
				$new_url = $new_url.(empty($query) ? '' : ((strpos($new_url, '?') === false) ? '?' : '&').$query);
				\ebi\Log::debug('URL rewrite: '.$url.' -> '.$new_url);
				return $new_url;
			}
		}
		return $url;
	}

	public static function url(string|array $url): string{
		if(\ebi\Conf::is_production()) return is_array($url) ? $url[0] : $url;
		[$url, $params] = is_array($url) ? [$url[0], array_slice($url, 1)] : [$url, []];
		if(strpos($url, '://') === false){
			$map_urls = self::get_urls();
			if(!empty($map_urls) && isset($map_urls[$url]) && substr_count($map_urls[$url], '%s') === count($params)){
				return vsprintf($map_urls[$url], $params);
			}
		}
		return $url;
	}

	private static function get_url_rewrite(): array{
		$patterns = \ebi\Conf::get('url_rewrite', []);
		$entry = \ebi\Conf::get('mock_entry_name', 'mock');
		foreach(self::$mock as $class_name){
			$inst = (new \ReflectionClass($class_name))->newInstance();
			foreach($inst->rewrite_map() as $pattern => $replacement){
				$patterns[$pattern] = $entry.'::'.str_replace('\\', '/', $class_name).
					(substr($replacement, 0, 1) === '/' ? $replacement : '/'.$replacement);
			}
		}
		return $patterns;
	}

	private static function get_urls(): array{
		$dir = getcwd();
		$urls = [];
		foreach(new \RecursiveDirectoryIterator($dir,
			\FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
		) as $f){
			if(substr($f->getFilename(), -4) === '.php' && !preg_match('/\/[\._]/', $f->getPathname())){
				$src = file_get_contents($f->getPathname());
				if(strpos($src, 'Flow') !== false){
					$entry_name = substr($f->getFilename(), 0, -4);
					$map = \ebi\Flow::get_map($f->getPathname());
					foreach($map['patterns'] as $m){
						$urls[$entry_name.'::'.$m['name']] = $m['format'];
					}
				}
			}
		}
		return $urls;
	}

	public static function testman_config(bool $autocommit=true): array{
		\ebi\Conf::set(\ebi\Db::class, 'autocommit', $autocommit);
		return [
			'urls' => self::get_urls(),
			'url_rewrite' => self::get_url_rewrite(),
			'ssl-verify' => false,
			'log_debug_callback' => '\\ebi\\Log::debug',
		];
	}

	public static function find_mail(string $to, string $tcode='', string $keyword=''): \ebi\SmtpBlackholeDao{
		return \ebi\SmtpBlackholeDao::find_mail($to, $tcode, $keyword);
	}

	public static function reset_tables(): void{
		foreach(self::classes(\ebi\Dao::class) as $class_info){
			$class = \ebi\Util::get_class_name($class_info['class']);
			call_user_func([$class, 'drop_table']);
			call_user_func([$class, 'create_table']);
		}
	}
}
