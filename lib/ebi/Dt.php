<?php
namespace ebi;

use ebi\Attribute\Route;

/**
 * 開発支援ツール
 * APIドキュメント生成(OpenAPI/Redoc)、設定一覧、送信メール確認、モックサーバーなどの機能を提供する
 */
class Dt extends \ebi\app\Request{
	private string $entry;
	private static array $mock = [];

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
			$this->entry = realpath($entryfile);
		}
		parent::__construct();
	}

	public function before(array $selected_pattern): void{
		parent::before($selected_pattern);

		/**
		 * @var string
		 * DevToolsのアクセスパスワード（未設定時は認証なし）
		 */
		$password = \ebi\Conf::get('password');

		if(!empty($password) && !$this->is_sessions('dt_login')){
			$action = $selected_pattern['action'] ?? '';
			if(strpos($action, '::login') === false && strpos($action, '::index') === false){
				$this->set_before_redirect('login');
			}
		}
	}

	#[Route]
	public function login(): void{
		$password = \ebi\Conf::get('password');
		/**
		 * @var string
		 * DevToolsのログインユーザー名
		 */
		$username = \ebi\Conf::get('username');

		if($this->is_vars('password')){
			if(
				$this->in_vars('password') === $password
				&& (empty($username) || $this->in_vars('username') === $username)
			){
				$this->sessions('dt_login', true);
				$this->sessions('dt_fail_count', 0);

				header('Content-Type: application/json');
				echo json_encode(['success' => true]);
				exit;
			}
			$count = (int)$this->in_sessions('dt_fail_count', 0) + 1;
			$this->sessions('dt_fail_count', $count);

			/**
			 * @var int
			 * ログイン失敗時に警告メールを送信する連続失敗回数（0で無効）
			 */
			$alert_threshold = (int)\ebi\Conf::get('alert_threshold', 5);
			/**
			 * @var string
			 * ログイン失敗警告メールの送信先
			 */
			$alert_to = \ebi\Conf::get('alert_to');

			if($alert_threshold > 0 && $count >= $alert_threshold && !empty($alert_to)){
				if(!$this->is_sessions('dt_alert_sent')){
					$this->sessions('dt_alert_sent', true);

					$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
					$forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
					$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
					$host = $_SERVER['HTTP_HOST'] ?? 'unknown';
					$time = date('Y-m-d H:i:s');

					$body = implode("\n", [
						"[DevTools] Login failure alert",
						"",
						"Consecutive failures: {$count}",
						"Time: {$time}",
						"Host: {$host}",
						"IP: {$ip}",
						!empty($forwarded) ? "X-Forwarded-For: {$forwarded}" : null,
						"User-Agent: {$ua}",
					]);
					$body = implode("\n", array_filter(explode("\n", $body), fn($v) => $v !== null));

					$mail = new \ebi\Mail();
					$mail->to($alert_to);
					$mail->from($alert_to);
					$mail->send('[Alert] DevTools login failure', $body);
				}
			}

			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'error' => 'Authentication failed']);
			exit;
		}

		$this->set_after_redirect((new \ebi\AppHelper())->package_method_url('index'));
	}

	/**
	 * Developer Tools Endpoints
	 */
	#[Route]
	public function index(): array{
		return $this->react_app_vars();
	}

	/**
	 * OpenAPI Specification (JSON)
	 */
	#[Route(suffix: '.json')]
	public function openapi(): void{
		$envelope = ($this->in_vars('envelope', '') === 'true');
		$include_dev = ($this->in_vars('include_dev', '') === 'true');
		$openapi = new \ebi\Dt\OpenApi($this->entry);
		$spec = $openapi->generate_spec($envelope, $include_dev);

		$result = $spec;
		if($include_dev){
			$mail_templates = [];
			foreach(\ebi\Dt\SourceAnalyzer::mail_template_list() as $info){
				$mail_templates[] = [
					'name' => $info->name(),
					'code' => $info->opt('x_t_code'),
					'subject' => $info->opt('subject') ?? '',
					'summary' => $info->document(),
				];
			}
			$result = [
				'spec' => $spec,
				'webhooks' => $openapi->get_webhooks(),
				'allTags' => $openapi->get_all_tags(),
				'mailTemplates' => $mail_templates,
			];
		}

		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');
		\ebi\HttpHeader::send('Access-Control-Allow-Origin', '*');
		echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	/**
	 * Redoc API Documentation
	 */
	#[Route]
	public function redoc(): void{
		$envelope = ($this->in_vars('envelope', '') === 'true');
		$spec = (new \ebi\Dt\OpenApi($this->entry))->generate_spec($envelope);
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
	#[Route(suffix: '.json')]
	public function sent_mails(): void{
		$mails = [];
		$pagination = null;

		try{
			$q = new Q();
			$q->add(Q::order('-id'));

			$tcode = (string)$this->in_vars('tcode', '');
			if($tcode !== ''){
				$q->add(Q::eq('tcode', $tcode));
			}
			$search = (string)$this->in_vars('search', '');
			if($search !== ''){
				$q->add(Q::ob(
					Q::contains('to', $search),
					Q::contains('from', $search),
					Q::contains('subject', $search)
				));
			}

			$paginator = new \ebi\Paginator(
				intval($this->in_vars('paginate_by', 20)),
				intval($this->in_vars('page', 1))
			);

			foreach(\ebi\SmtpBlackholeDao::find_all($q, $paginator) as $mail){
				$mails[] = [
					'id' => $mail->id(),
					'from' => $mail->from(),
					'to' => trim($mail->to()),
					'subject' => $mail->subject(),
					'message' => $mail->message(),
					'tcode' => $mail->tcode(),
					'create_date' => date('Y-m-d H:i:s', $mail->create_date()),
				];
			}
			$pagination = [
				'current' => $paginator->current(),
				'pages' => $paginator->last(),
				'total' => $paginator->total(),
				'limit' => $paginator->limit(),
			];
		}catch(\Exception){
		}

		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');
		echo json_encode(['mails' => $mails, 'pagination' => $pagination], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * Configs API - List all Conf::get/gets definitions
	 */
	#[Route(suffix: '.json')]
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
					$document = trim($conf_info->document());
					$summary = $conf_info->summary();
					if(empty($summary) && $conf_info->has_params()){
						$summary = $conf_info->param()->summary();
						if(!empty($summary)){
							$document = $summary;
						}
					}
					$configs[] = [
						'class' => $info->name(),
						'name' => $name,
						'summary' => $summary,
						'document' => $document,
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

	/**
	 * Scanned Classes API - List all auto-scanned classes
	 */
	#[Route(suffix: '.json')]
	public function scanned_classes(): void{
		$classes = [];

		foreach(self::classes() as $class_info){
			$classes[] = [
				'class' => ltrim($class_info['class'], '\\'),
				'filename' => $class_info['filename'],
			];
		}

		usort($classes, fn($a, $b) => strcmp($a['class'], $b['class']));

		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');
		echo json_encode(['classes' => $classes, 'total' => count($classes)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * Mocks API - List all registered mock classes
	 */
	#[Route(suffix: '.json')]
	public function mocks(): void{
		$mocks = [];

		foreach(self::$mock as $class_name){
			$mock_info = [
				'class' => $class_name,
			];
			try{
				$inst = (new \ReflectionClass($class_name))->newInstance();
				$mock_info['rewrite_map'] = $inst->rewrite_map();
			}catch(\Exception){
				$mock_info['rewrite_map'] = [];
			}
			$mocks[] = $mock_info;
		}

		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');
		echo json_encode(['mocks' => $mocks, 'total' => count($mocks)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * phpinfo
	 */
	#[Route]
	public function phpinfo(): void{
		phpinfo();
		exit;
	}

	// === Render Methods ===

	private function react_app_vars(): array{
		$has_smtp_blackhole = false;
		try{
			\ebi\SmtpBlackholeDao::find_count();
			$has_smtp_blackhole = true;
		}catch(\Exception){
		}

		$password = \ebi\Conf::get('password');
		$authenticated = empty($password) || $this->is_sessions('dt_login');

		$helper = new \ebi\AppHelper();
		$urls = json_encode([
			'openapi' => $helper->package_method_url('openapi'),
			'redoc' => $helper->package_method_url('redoc'),
			'sent_mails' => $helper->package_method_url('sent_mails'),
			'configs' => $helper->package_method_url('configs'),
			'scanned_classes' => $helper->package_method_url('scanned_classes'),
			'mocks' => $helper->package_method_url('mocks'),
			'phpinfo' => $helper->package_method_url('phpinfo'),
			'login' => $helper->package_method_url('login'),
		], JSON_UNESCAPED_SLASHES);

		$app_js = file_get_contents(__DIR__.'/Dt/assets/app.js');
		$app_css = '';
		if(is_file(__DIR__.'/Dt/assets/app.css')){
			$app_css = file_get_contents(__DIR__.'/Dt/assets/app.css');
		}

		return [
			'title' => $authenticated ? 'Developer Tools' : 'Login',
			'urls' => $urls,
			'has_smtp_blackhole' => $has_smtp_blackhole ? 'true' : 'false',
			'appmode' => \ebi\Conf::appmode(),
			'authenticated' => $authenticated ? 'true' : 'false',
			'requires_password' => !empty($password) ? 'true' : 'false',
			'app_js' => $app_js,
			'app_css' => $app_css,
		];
	}

	private function get_schema_type(string $class): string{
		if(is_subclass_of($class, \ebi\Dao::class)) return 'model';
		if(is_subclass_of($class, \ebi\app\Request::class)) return 'request';
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

		/**
		 * @var array
		 * スキャン対象に含めるvendorクラス
		 * 末尾に*を付けるとそのパッケージ配下を全て読み込む
		 */
		$use_vendor = \ebi\Conf::gets('use_vendor');
		/**
		 * @var string
		 * スキャン対象のvendorクラスを返すコールバック関数
		 */
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

		// ebi内部のユーティリティDaoはuse_vendorで明示的に追加された場合のみ含める
		$internal_dao_classes = [
			'ebi\\SmtpBlackholeDao',
			'ebi\\SessionDao',
			'ebi\\UserRememberMeDao',
		];
		$use_vendor_set = [];
		foreach($use_vendor as $v){
			$use_vendor_set[ltrim(str_replace('*', '', $v), '\\')] = true;
		}

		$yielded = [];
		foreach(get_declared_classes() as $class){
			$r = new \ReflectionClass($class);
			$real_name = $r->getName();
			if(!$r->isInterface()
				&& !isset($yielded[$real_name])
				&& (empty($parent_class) || is_subclass_of($real_name, $parent_class))
				&& $r->getFileName() !== false
				&& $real_name[0] !== '_' && strpos($real_name, '\\_') === false
				&& strpos($real_name, 'Composer') === false
				&& strpos($real_name, 'cmdman') === false
				&& strpos($real_name, 'testman') === false
				&& (!in_array($real_name, $internal_dao_classes) || isset($use_vendor_set[$real_name]))
			){
				$yielded[$real_name] = true;
				yield ['filename' => $r->getFileName(), 'class' => '\\'.$real_name];
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
				self::$_urls_cache = null;
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
		/**
		 * @var array
		 * URLリライトルール [ 正規表現パターン => 置換先 ]
		 */
		$patterns = \ebi\Conf::get('url_rewrite', []);
		/**
		 * @var string
		 * モックURLを生成する際のエントリ名、デフォルトは mock
		 */
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

	private static ?array $_urls_cache = null;

	private static function get_urls(): array{
		if(self::$_urls_cache !== null){
			return self::$_urls_cache;
		}
		$dir = getcwd();
		$urls = [];
		foreach(new \RecursiveDirectoryIterator($dir,
			\FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
		) as $f){
			if(substr($f->getFilename(), -4) === '.php' && !preg_match('/\/[\._]/', $f->getPathname())){
				$src = file_get_contents($f->getPathname());
				if(strpos($src, 'ebi\\Flow') !== false || strpos($src, 'ebi\\App') !== false){
					$entry_name = substr($f->getFilename(), 0, -4);
					$map = \ebi\App::get_map($f->getPathname());
					foreach($map['patterns'] as $m){
						$urls[$entry_name.'::'.$m['name']] = $m['format'];
					}
				}
			}
		}
		self::$_urls_cache = $urls;
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
			$r = new \ReflectionClass($class_info['class']);
			if($r->isAbstract()){
				continue;
			}
			$class = \ebi\Util::get_class_name($class_info['class']);
			call_user_func([$class, 'drop_table']);
			call_user_func([$class, 'create_table']);
		}
	}
}
