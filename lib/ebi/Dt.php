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
			$bearer = $this->verify_bearer_token();

			if($bearer === true){
				// Bearer認証済み（APIクライアント等）: セッションを作らずそのまま許可する
				return;
			}
			if($bearer === false){
				// Bearerヘッダはあるがトークンが不正: HTMLログインへ飛ばさず401を返す
				\ebi\HttpAuthorizationBearer::send_error_header(401, 'DevTools', 'invalid access token');
				exit;
			}

			// Bearer未指定: 従来どおりCookie（セッション）ベースのログインへ誘導する
			$action = $selected_pattern['action'] ?? '';
			if(strpos($action, '::login') === false && strpos($action, '::index') === false){
				$this->set_before_redirect('login');
			}
		}
	}

	/**
	 * Authorizationヘッダ（Bearer）によるアクセス可否を判定する
	 *
	 * @return bool|null true:有効なトークン / false:トークン不一致 / null:Bearer認証無効またはヘッダ無し（Cookie認証へフォールバック）
	 */
	private function verify_bearer_token(): ?bool{
		/**
		 * @var string
		 * DevToolsへBearer認証でアクセスするためのアクセストークン。
		 * 設定するとAuthorizationヘッダ "Bearer <token>" でCookie無しにアクセスできる（未設定時はBearer認証無効）。
		 * 配列で複数指定した場合はローテーション用に複数トークンを許可する。
		 */
		$configured = \ebi\Conf::get('bearer_token');

		if(empty($configured)){
			return null;
		}
		$token = \ebi\HttpAuthorizationBearer::get_token();
		if(empty($token)){
			return null;
		}
		foreach((is_array($configured) ? $configured : [$configured]) as $t){
			if(is_string($t) && $t !== '' && hash_equals($t, $token)){
				return true;
			}
		}
		return false;
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
				$this->set_after_redirect((new \ebi\AppHelper())->package_method_url('index'));
				return;
			}
			$count = (int)$this->in_sessions('dt_fail_count', 0) + 1;
			$this->sessions('dt_fail_count', $count);
			$this->sessions('dt_login_error', true);

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
		$envelope = $this->is_vars('envelope') ? ($this->in_vars('envelope') === 'true') : \ebi\App::envelope_default();
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
	 * MCP (Model Context Protocol) エンドポイント
	 *
	 * dt が生成する OpenAPI をもとに、アプリの API ドキュメントを MCP から検索・参照できるようにする（読み取り専用）。
	 * 第三者製ブリッジを介さず ebi 自身が JSON-RPC 2.0(Streamable HTTP) を喋る。
	 * 既定は無効で、Conf(\ebi\Dt) の mcp_enabled を true にしたときのみ有効になる。
	 */
	#[Route]
	public function mcp(): void{
		/**
		 * @var bool
		 * MCPエンドポイント(/dt/mcp)を有効にする（既定 false = 無効。無効時は404を返す）
		 */
		if(!\ebi\Conf::get('mcp_enabled', false)){
			\ebi\HttpHeader::send_status(404);
			exit;
		}

		// Streamable HTTP: 本サーバは POST(JSON-RPC) のみ対応。
		// クライアントが server→client 用の SSE ストリームを開こうとする GET 等には
		// 405 を返して「SSEストリーム無し」を明示する（返さないと再接続ループになる）。
		if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'){
			\ebi\HttpHeader::send('Allow', 'POST');
			\ebi\HttpHeader::send_status(405);
			exit;
		}

		\ebi\HttpHeader::send('Content-Type', 'application/json; charset=utf-8');

		$raw = file_get_contents('php://input');
		$payload = json_decode((string)$raw, true);

		if(!is_array($payload)){
			echo json_encode(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32700, 'message' => 'Parse error']]);
			exit;
		}

		// envelope は App と同じ Accept ヘッダ判定に従う（Accept: application/json; envelope=true、無ければ既定）。
		$mcp = new \ebi\Dt\Mcp($this->entry, \ebi\App::is_envelope());

		// バッチ(JSON配列) と 単一(JSONオブジェクト) の両対応。
		// 単一リクエストは連想配列(jsonrpc/method等)、バッチは0始まりのリスト。
		$is_batch = array_key_exists(0, $payload) && is_array($payload[0]);
		$requests = $is_batch ? $payload : [$payload];

		$responses = [];
		foreach($requests as $req){
			if(!is_array($req)){
				continue;
			}
			$res = $mcp->handle($req);
			if($res !== null){
				$responses[] = $res;
			}
		}

		if(empty($responses)){
			// 通知のみ（応答不要）
			\ebi\HttpHeader::send_status(202);
			exit;
		}

		echo json_encode($is_batch ? $responses : $responses[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
	}

	/**
	 * Redoc API Documentation
	 */
	#[Route]
	public function redoc(): void{
		$envelope = $this->is_vars('envelope') ? ($this->in_vars('envelope') === 'true') : \ebi\App::envelope_default();
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
		$login_error = $this->is_sessions('dt_login_error');
		if($login_error){
			$this->rm_sessions('dt_login_error');
		}

		$mcp_enabled = (bool)\ebi\Conf::get('mcp_enabled', false);

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
			'mcp' => $helper->package_method_url('mcp'),
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
			'login_error' => $login_error ? 'true' : 'false',
			'mcp_enabled' => $mcp_enabled ? 'true' : 'false',
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

	/**
	 * 並列テスト(testman -p)の worker スロット番号。testman が各 worker subprocess に渡す
	 * TESTMAN_WORKER_ID を読む。並列でない/未設定なら 0。
	 * これを唯一のソースに、DB名やストレージ、URLホストを worker 毎に分離する。
	 */
	public static function worker_id(): int{
		$id = getenv('TESTMAN_WORKER_ID');
		return ($id !== false && (int)$id > 0) ? (int)$id : 0;
	}

	/**
	 * worker 毎リソースの分離サフィックス（例 _w3）。worker でなければ空文字列。
	 * SqliteConnector が DB ファイル名へ、アプリが work_dir 等へ付与する。
	 */
	public static function worker_suffix(): string{
		$id = self::worker_id();
		return $id > 0 ? '_w'.$id : '';
	}

	/** derive_base_port() の結果キャッシュ。0 = 未評価 */
	private static int $_derived_port_cache = 0;

	/** ride_along_serve() の結果キャッシュ。false = 未評価 */
	private static $_ride_along_cache = false;

	/** worker_setup() で宣言された希望ベースポート（直列/手動で使う既定）。null = 未宣言 */
	private static ?int $_preferred_port = null;

	/** ベースポートの割り当て: BASE_PORT_MIN + (0..SLOTS-1) * STRIDE（= 8000..9780 を 20 刻み） */
	private const BASE_PORT_MIN = 8000;
	private const BASE_PORT_SLOTS = 90;
	private const BASE_PORT_STRIDE = 20;

	/**
	 * ebi 同梱ルーターの絶対パス。testman の serve 雛形・孤児サーバ掃除のシグネチャ・
	 * ベースポート導出が、全てこの1つの文字列を見る。
	 */
	public static function serve_router_path(): string{
		return dirname(__DIR__, 2).'/resources/test_router.php';
	}

	/**
	 * テスト/ローカル用サーバのベースポート。解決順は次のとおり:
	 *  1. env TESTMAN_BASE_PORT（testman がポート確定後に注入する。worker/並列retry はここ）
	 *  2. 並列実行(testman -p)なら router から決定的に導出（下記の理由で希望ポートは使わない）
	 *  3. 手動起動サーバ(cmdman ebi.Dt::serve start 等)が動いていれば、そのポート ＝ 相乗り（直列のみ）
	 *  4. 希望ポート（$default_port か worker_setup で宣言された $_preferred_port）
	 *  5. router の絶対パスから決定的に導出した値
	 *
	 * 並列(2)を希望ポートより先に導出へ倒すのは、並列が base..base+workers の連続ポートを要し、
	 * かつ retry が「親プロセスで直列 include」される testman の仕様上、親の settings ロード時点で
	 * 希望ポート(8881等)を焼き付けると worker/serve の導出値とズレ、直列retryが誰も居ない
	 * 希望ポートを叩いて connection refused になるため。導出値は env の有無に依らず決定的なので、
	 * 親ロード・worker・retry すべてが同じ値に揃う。
	 *
	 * 一方 直列/手動(3〜5)は連続ポート不要かつ「テスト後の値を manage 等で目視確認したい」ため、
	 * 希望ポート(既定 8881)をそのまま使う。稼働中の手動サーバがあればそれに相乗りする。
	 */
	public static function base_port(?int $default_port = null): int{
		$env = getenv('TESTMAN_BASE_PORT');
		if($env !== false && $env !== ''){
			return (int)$env;
		}
		// 並列は連続ポートが要る＆retryが親直列のため、常に導出（希望ポートは使わない）。
		if(self::is_parallel_run()){
			return self::derive_base_port();
		}
		// 直列/手動: 相乗り → 希望ポート → 導出 の順。
		$manual = self::ride_along_serve();
		if($manual !== null){
			return (int)$manual['port'];
		}
		$preferred = $default_port ?? self::$_preferred_port;

		return ($preferred !== null) ? $preferred : self::derive_base_port();
	}

	/**
	 * 相乗りできる手動起動サーバ(cmdman ebi.Dt::serve start)。無ければ null。
	 * 相乗りするのは「直列実行」かつ「--serve / --serve-port / --no-serve の明示が無い」場合だけ。
	 * 並列実行は連続ポートが要るので相乗りしない（testman が自前でサーバ群を立てる）。
	 * 結果はプロセス内でキャッシュする。base_port() は何度も呼ばれるため ps を都度叩かない。
	 */
	public static function ride_along_serve(): ?array{
		if(self::$_ride_along_cache !== false){
			return self::$_ride_along_cache;
		}
		self::$_ride_along_cache = null;

		if(!self::is_parallel_run() && !self::has_serve_cli_opt()){
			$list = self::running_serves(null, self::serve_router_path());
			self::$_ride_along_cache = empty($list) ? null : $list[0];
		}
		return self::$_ride_along_cache;
	}

	/**
	 * testman が並列実行(-p / --parallel)で起動されたか。
	 * settings ロード時点では Runner がまだ実効値を決めていないため、CLI 引数を直接見る。
	 */
	private static function is_parallel_run(): bool{
		if(!class_exists('\testman\Args')){
			return false;
		}
		return (\testman\Args::opt('parallel', false) !== false) || (\testman\Args::opt('p', false) !== false);
	}

	/**
	 * serve 関連の CLI 指定があるか。明示されているならユーザの意図を優先し、相乗り判定はしない。
	 */
	private static function has_serve_cli_opt(): bool{
		if(!class_exists('\testman\Args')){
			return false;
		}
		return (\testman\Args::opt('serve', false) !== false)
			|| (\testman\Args::opt('serve-port', false) !== false)
			|| \testman\Args::has_opt('no-serve');
	}

	/**
	 * serve が状態を書き出す PIDファイルのパス（$listen は 'localhost:8888' 形式）
	 */
	public static function serve_pid_file(string $listen): string{
		return sys_get_temp_dir().'/ebi-serve-'.preg_replace('/[^a-zA-Z0-9_.\-]/','_',$listen).'.pid';
	}

	/**
	 * serve の PIDファイルを読み、起動中であれば状態を返す。
	 * PID再利用で無関係なプロセスを掴まないよう、コマンドラインの一致も確認する。
	 * 停止済みのPIDファイルはここで削除する（読み手が掃除する ＝ 停止側が落ちても溜まらない）。
	 */
	public static function read_serve(string $file): ?array{
		if(!is_file($file) || !function_exists('posix_kill')){
			return null;
		}
		$state = json_decode((string)file_get_contents($file), true);
		$alive = false;

		if(is_array($state) && !empty($state['pid']) && !empty($state['listen'])){
			$cmdline = (string)@shell_exec('ps -o command= -p '.escapeshellarg((string)(int)$state['pid']).' 2>/dev/null');
			$alive = (@posix_kill((int)$state['pid'], 0) && strpos($cmdline, '-S '.$state['listen']) !== false);
		}
		if(!$alive){
			@unlink($file);
			return null;
		}
		$state['file'] = $file;
		$state['port'] = (int)substr((string)$state['listen'], strrpos((string)$state['listen'], ':') + 1);
		return $state;
	}

	/**
	 * 起動中の serve を列挙する。$docroot / $router を指定するとそれに一致するものだけを返す。
	 * $router 一致は「同じ checkout のサーバか」の判定に使う（docroot は起動時の cwd 次第で揺れるため）。
	 */
	public static function running_serves(?string $docroot = null, ?string $router = null): array{
		$list = [];

		foreach((array)glob(sys_get_temp_dir().'/ebi-serve-*.pid') as $file){
			$state = self::read_serve($file);

			if($state === null){
				continue;
			}
			if($docroot !== null && ($state['docroot'] ?? null) !== $docroot){
				continue;
			}
			if($router !== null && ($state['router'] ?? null) !== $router){
				continue;
			}
			$list[] = $state;
		}
		return $list;
	}

	/**
	 * router の絶対パスからベースポートを導出する。
	 * アンカーに router パスを使うのは、testman の孤児サーバ掃除が同じ文字列をシグネチャにしており
	 * 「router パスはプロジェクト毎に一意」という前提が既に存在するため（別プロジェクトを巻き込まない）。
	 * base..base+workers を確保できるよう STRIDE 間隔で割り当てる。
	 * ハッシュが衝突しても testman がポート占有を検出して明示エラーにするため、黙って混線はしない。
	 */
	private static function derive_base_port(): int{
		if(self::$_derived_port_cache !== 0){
			return self::$_derived_port_cache;
		}
		$slot = (int)(crc32(self::serve_router_path()) % self::BASE_PORT_SLOTS);
		$used = array_column(self::running_serves(null, self::serve_router_path()), 'port');
		$base = self::BASE_PORT_MIN + ($slot * self::BASE_PORT_STRIDE);

		// 手動サーバが自分のブロックに居るならブロックごとずらす。
		// 並列実行は base..base+workers を占有するため、ずらさないと手動サーバを追い出してしまう。
		for($i = 0; $i < self::BASE_PORT_SLOTS; $i++){
			$candidate = self::BASE_PORT_MIN + ((($slot + $i) % self::BASE_PORT_SLOTS) * self::BASE_PORT_STRIDE);
			$occupied = false;

			foreach($used as $port){
				if($port >= $candidate && $port < ($candidate + self::BASE_PORT_STRIDE)){
					$occupied = true;
					break;
				}
			}
			if(!$occupied){
				$base = $candidate;
				break;
			}
		}
		return self::$_derived_port_cache = $base;
	}

	/**
	 * アプリ自身のホスト(host:port)を worker 対応で解決する。app_url/flow_url 等の自己参照URLに使う。
	 *  - HTTP リクエスト内(server プロセス)は HTTP_HOST(=自ポート)。
	 *  - HTTP_HOST が無い CLI(testman worker subprocess 等)は worker_id から自 worker のポート
	 *    (base_port() + slot)を導出。これで in-process の自己参照が自 worker のサーバへ着弾する。
	 *  - どちらも無い直列実行は localhost:<base_port()>。
	 * 本番は常に HTTP_HOST があるため即 return＝挙動不変。worker 分岐は TESTMAN_WORKER_ID 前提。
	 */
	public static function self_host(?int $default_port = null): string{
		if(isset($_SERVER['HTTP_HOST'])){
			return $_SERVER['HTTP_HOST'];
		}
		$wid = self::worker_id();
		$base = self::base_port($default_port);
		return 'localhost:'.($wid > 0 ? $base + $wid : $base);
	}

	/**
	 * アプリ自身のベースURL＋パスを組み立てる（scheme は http 固定＝テスト/ローカル用、ホストは self_host()）。
	 * app_url/flow_url 等、自 worker のサーバへ戻す必要があるURLに使う。
	 * 例: base_url('/api/payments/') → 'http://localhost:<base_port()>/api/payments/'（worker は base+slot）。
	 */
	public static function base_url(string $path = '/', ?int $default_port = null): string{
		return 'http://'.self::self_host($default_port).$path;
	}

	/**
	 * worker 対応のテスト/ローカル設定を ebi\Conf へ適用し、work_dir(書込先ディレクトリ)を返す。
	 * 設定内容:
	 *   - work_dir : $storage_base.'work'[_w<id>].'/'（並列は worker 毎に分離、直列は 'work/'）
	 *   - app_url  : self_host($default_port) を用いた自己参照URLのホスト
	 * 返り値の work_dir は material 等の派生パスを組む用途に使える（base を再指定しなくてよい）。
	 * $default_port は直列/HTTP_HOST無し時のベースポート(worker は +slot)。省略時は base_port() の導出値
	 * （env TESTMAN_BASE_PORT があればそちらが優先）。固定したい事情が無ければ省略すること。
	 * 使い方: $work_dir = \ebi\Dt::worker_setup($storage_base); を他の Conf::set より前に呼ぶ
	 * （ebi\Conf::set は先勝ちマージのため）。app_url を独自にしたい場合はこれより前に set する。
	 */
	public static function worker_setup(string $storage_base, ?int $default_port = null): string{
		// 希望ポートを覚えておき、引数を渡せない base_port()（testman_config 等）でも同じ値を使えるようにする。
		if($default_port !== null){
			self::$_preferred_port = $default_port;
		}
		$work_dir = rtrim($storage_base, '/').'/work'.self::worker_suffix().'/';
		\ebi\Conf::set([
			'ebi\Conf' => ['work_dir' => $work_dir],
			'ebi\App'  => ['app_url'  => 'http://'.self::self_host($default_port).'/*'],
		]);
		return $work_dir;
	}

	/**
	 * PHP built-in server (php -S) 用のルーター本体。
	 * URI の先頭セグメントをエントリ名として <docroot>/<entry>.php を include する。
	 * テスト/ローカルで ebi アプリを php -S で動かすための共通ルーター。
	 *
	 * アプリ側の test_router.php は次のスタブでよい:
	 *   <?php require __DIR__.'/vendor/autoload.php'; \ebi\Dt::serve_router(__DIR__);
	 *
	 * @param string|null $docroot エントリ .php を探すディレクトリ（既定: getcwd()）
	 */
	public static function serve_router(?string $docroot = null): void{
		$docroot = $docroot ?? getcwd();

		$request_url = $_SERVER['REQUEST_URI'] ?? '';
		$remote_addr = ($_SERVER['REMOTE_ADDR'] ?? '').':'.($_SERVER['REMOTE_PORT'] ?? '');
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$current_time = date('D M d H:i:s Y');
		$worker_count = getenv('PHP_CLI_SERVER_WORKERS');
		$worker = ($worker_count !== false && (int)$worker_count > 1) ? sprintf('[%s] ', getmypid()) : '';

		$exp = explode('/', substr($request_url, 1), 2);
		$entry = $exp[0];
		$pathinfo = $exp[1] ?? '';
		$entry_file = $docroot.'/'.$entry.'.php';

		$out = fopen('php://stdout', 'w');
		if(is_file($entry_file)){
			$_SERVER['PATH_INFO'] = '/'.$pathinfo;
			try{
				include($entry_file);
			}catch(\Throwable $e){
				error_log((string)$e);
				http_response_code(500);
				if(ini_get('display_errors')){
					print('<pre>'.htmlspecialchars((string)$e).'</pre>');
				}
			}
			$status = http_response_code();
			$color = ($status == 200) ? 32 : (($status == 404) ? 31 : 33);
			$log = $worker.sprintf('[%s] %s [%s]: %s %s', $current_time, $remote_addr, $status, $method, $request_url);
			fwrite($out, sprintf("\033[0;0:%sm%s\033[0m", $color, $log).PHP_EOL);
		}else{
			$log = $worker.sprintf('[%s] %s [%s]: %s %s', $current_time, $remote_addr, 404, $method, $request_url);
			fwrite($out, sprintf("\033[0;0:31m%s\033[0m", $log).PHP_EOL);
			http_response_code(404);
			print('404 Not Found');
		}
		fclose($out);
	}

	public static function testman_config(bool $autocommit=true): array{
		\ebi\Conf::set(\ebi\Db::class, 'autocommit', $autocommit);

		$urls = self::get_urls();
		$url_rewrite = self::get_url_rewrite();

		// 並列テスト: worker は専用サーバ(ポート = base + worker_id)へ振り分ける。
		// urls / url_rewrite に埋め込まれた base ポートの host を worker 専用ポートへ置換する。
		// testman の base ポート。settings ロード時点では TESTMAN_BASE_PORT が未設定のため、
		// self_host() の既定と同じ値になる必要がある（ずれると worker が別ポートへ飛ぶ）。
		// base_port() は env の有無に依らず同じ値を返すので、この一致が構造的に保たれる。
		$base = self::base_port();
		$wid = self::worker_id();
		if($wid > 0){
			$from = 'localhost:'.$base;
			$to = 'localhost:'.($base + $wid);
			$rewrite_host = function($v) use (&$rewrite_host, $from, $to){
				if(is_string($v)){
					return str_replace($from, $to, $v);
				}
				if(is_array($v)){
					$out = [];
					foreach($v as $k => $e){ $out[$k] = $rewrite_host($e); }
					return $out;
				}
				return $v;
			};
			$urls = $rewrite_host($urls);
			$url_rewrite = $rewrite_host($url_rewrite);
		}

		// testman --serve 用の既定: ebi 同梱ルーターで php built-in server を起動する。
		// testman は CLI 未指定時にこの Conf 値を使う（{port} は testman が置換、
		// TESTMAN_WORKER_ID / TESTMAN_DOCROOT / TESTMAN_BASE_PORT は testman が注入）。
		// 起動待ちは testman が既定で TCP 接続を確認するため、待機先パスの設定は不要。
		$router = self::serve_router_path();

		// 手動起動サーバへ相乗りする場合は serve を渡さない。渡すと testman が
		// 自前のサーバを立てるだけでなく、起動時の孤児掃除(router パス一致)で
		// 手動サーバまで落としてしまう。null なら testman はサーバに一切触らない。
		$manual = self::ride_along_serve();

		if($manual !== null && class_exists('\testman\Std')){
			\testman\Std::println_info(sprintf('  using manual server: http://%s/ (pid %s)', $manual['listen'], $manual['pid']));
		}

		return [
			'urls' => $urls,
			'url_rewrite' => $url_rewrite,
			'ssl-verify' => false,
			'log_debug_callback' => '\\ebi\\Log::debug',
			'serve' => ($manual !== null) ? null : 'PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:{port} '.escapeshellarg($router),
			// base ポートを testman の自動選択に任せるとアプリの自己参照URL(self_host)とずれる。
			// base_port() はプロジェクト毎に決定的なので、固定して渡しつつ同時実行も成立する。
			'serve_port' => $base,
			'teardown' => '\\ebi\\Dt::clean_worker_env',
		];
	}

	/**
	 * 並列テストで worker 毎に作られた資源を削除する（testman の Conf 'teardown' から呼ばれる）。
	 * testman は実行の最後に親プロセスで一度だけ呼び、使用した slot 数を渡してくる。
	 *
	 * worker の DB ファイルは \ebi\SqliteConnector が TESTMAN_WORKER_ID を見て
	 * 拡張子の直前へ _w<id> を挿入した名前（例 data.sqlite3 → data_w3.sqlite3）で作る。
	 * その命名に一致するものだけを対象にする。
	 *
	 * @param array $info ['workers'=>int, 'interrupted'=>bool, 'cwd'=>string]
	 */
	public static function clean_worker_env(array $info): void{
		$workers = (int)($info['workers'] ?? 0);
		$cwd = (string)($info['cwd'] ?? getcwd());

		for($id=1;$id<=$workers;$id++){
			foreach((array)glob($cwd.'/*_w'.$id.'.sqlite3') as $file){
				if(is_file($file)){
					unlink($file);
				}
			}
		}
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

	/**
	 * ebi\Dt@flow_batch_classes に登録されたクラスの静的メソッドを走査し、#[Batch] を持つものを x-flow-batches として収集する。
	 */
	public static function get_flow_batch_classes(): array{
		/**
		 * バッチアクター（クラス名の配列）を登録する
		 *  @var array
		 */
		$classes = \ebi\Conf::get('flow_batch_classes', []);

		return $classes;
	}
}
