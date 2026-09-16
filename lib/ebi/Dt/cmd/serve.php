<?php
/**
 * PHP built-in server (subcommand: start / stop / restart / status)
 * @param int $port Port number (default: \ebi\Dt::base_port() = このプロジェクト固有) @['short'=>'p','init'=>0]
 * @param string $host Bind host @['init'=>'localhost']
 * @param int $workers Worker processes (PHP_CLI_SERVER_WORKERS) @['short'=>'w','init'=>4]
 * @param string $docroot Directory to look up entry .php (default: current dir) @['short'=>'d']
 * @param string $pid PID file path (default: system temp dir) @['short'=>'i']
 * @param boolean $all List servers of every docroot (status)
 */

$values = \cmdman\Args::values();
$sub = (string)array_shift($values);

if(!in_array($sub,['','start','stop','restart','status'],true)){
	\cmdman\Std::println_danger('Unknown subcommand: '.$sub);
	\cmdman\Std::println('start / stop / restart / status (no subcommand: run in foreground)');
	\cmdman\Util::exit_error();
}
// testman --serve と同じ ebi 同梱ルーターを使う（先頭セグメント -> <entry>.php へ振り分け）
$router = \ebi\Dt::serve_router_path();

if(!is_file($router)){
	\cmdman\Std::println_danger('Router not found: '.$router);
	\cmdman\Util::exit_error();
}
if(!is_dir($docroot = (empty($docroot) ? getcwd() : $docroot))){
	\cmdman\Std::println_danger('Docroot not found: '.$docroot);
	\cmdman\Util::exit_error();
}
// ルーターは別プロセスで動くため、相対指定でも絶対パスに正規化して渡す
$docroot = str_replace("\\",'/',realpath($docroot));
$workers = ($workers < 1) ? 1 : $workers;

// 接続先が明示されたか。未指定なら docroot が一致するサーバを自動で特定する
$specified = (
	\cmdman\Args::opt('port') !== false || \cmdman\Args::opt('p') !== false ||
	\cmdman\Args::opt('host') !== false ||
	\cmdman\Args::opt('pid') !== false || \cmdman\Args::opt('i') !== false
);
if(empty($port)){
	// ポート未指定なら、このプロジェクトのテスト用ベースポートを起点にする。
	// testman が立てるサーバと同じ起点にすることで、手動サーバとテストのポート体系が揃い、
	// 別プロジェクトの手動サーバと 8000 を取り合うこともなくなる。
	$port = \ebi\Dt::base_port();
}
// PIDファイルの読み書きは \ebi\Dt に集約している。テスト側(base_port の相乗り判定)が
// 同じ実装で状態を読むため、書式や生存判定がここと二重定義にならないようにする。
$pid_path = fn(string $listen): string => empty($pid) ? \ebi\Dt::serve_pid_file($listen) : $pid;
$log_path = fn(string $file): string => (substr($file,-4) === '.pid') ? substr($file,0,-4).'.log' : $file.'.log';

$read = fn(string $file): ?array => \ebi\Dt::read_serve($file);
$scan = fn(?string $only_docroot): array => \ebi\Dt::running_serves($only_docroot);

/**
 * 操作対象の起動中サーバを列挙する
 */
$targets = function(string $listen) use($read,$scan,$specified,$pid_path,$docroot): array{
	if($specified){
		$state = $read($pid_path($listen));
		return ($state === null) ? [] : [$state];
	}
	// 接続先未指定。他プロジェクトのサーバを巻き込まないよう docroot 一致のみを対象にする
	return $scan($docroot);
};

/**
 * ポートが空いているか（他プロジェクトや無関係なプロセスの占有を検出する）
 */
$port_free = function(string $host, int $port): bool{
	// 接続失敗の警告は想定内。`@` を無視するエラーハンドラが入っていても例外化させない
	set_error_handler(fn() => true);
	$sock = fsockopen($host,$port,$errno,$errstr,0.2);
	restore_error_handler();

	if($sock === false){
		return true;
	}
	fclose($sock);
	return false;
};

$print_list = function(array $list): void{
	foreach($list as $state){
		\cmdman\Std::println('   http://'.$state['listen'].'/ (pid '.$state['pid'].') '.$state['docroot']);
	}
};

/**
 * 他プロジェクトのサーバを操作しようとしていないか確認する
 */
$assert_owned = function(array $state) use($docroot): void{
	if(($state['docroot'] ?? null) === $docroot){
		return;
	}
	\cmdman\Std::println_danger('http://'.$state['listen'].'/ belongs to another docroot:');
	\cmdman\Std::println('   '.$state['docroot']);
	\cmdman\Std::println('Run it from that directory, or add -d '.escapeshellarg((string)$state['docroot']));
	\cmdman\Util::exit_error();
};

/**
 * バックグラウンドで起動する
 */
$start = function(string $listen, int $workers) use($read,$scan,$specified,$pid_path,$log_path,$port_free,$print_list,$router,$docroot,$host,$port): void{
	if($specified){
		// 接続先が明示された場合、その接続先だけを見る
		if(null !== ($state=$read($pid_path($listen)))){
			if(($state['docroot'] ?? null) === $docroot){
				\cmdman\Std::println_warning('Already running (pid '.$state['pid'].'): http://'.$listen.'/');
			}else{
				\cmdman\Std::println_danger('http://'.$listen.'/ is already used by another docroot:');
				\cmdman\Std::println('   '.$state['docroot']);
				\cmdman\Util::exit_error();
			}
			return;
		}
		if(!$port_free($host,$port)){
			\cmdman\Std::println_danger('Port '.$port.' is already in use.');
			\cmdman\Util::exit_error();
		}
	}else{
		// 接続先未指定。同じdocrootで既に動いていればポートを問わず再利用させる
		if(!empty($list=$scan($docroot))){
			\cmdman\Std::println_warning('Already running (pid '.$list[0]['pid'].'): http://'.$list[0]['listen'].'/');
			return;
		}
		// 既定ポートが他プロジェクト等に使われていれば、空いているポートまでずらす。
		// 複数プロジェクトを同時に立ち上げてもポート指定なしで済ませるため。
		$found = null;

		for($p=$port;$p<($port + 20);$p++){
			if($port_free($host,$p) && $read($pid_path($host.':'.$p)) === null){
				$found = $p;
				break;
			}
		}
		if($found === null){
			\cmdman\Std::println_danger('No free port in '.$port.'-'.($port + 19));
			\cmdman\Util::exit_error();
		}
		$listen = $host.':'.$found;
	}
	$file = $pid_path($listen);
	$log = $log_path($file);

	// ルーターは TESTMAN_DOCROOT からエントリを探す（未設定時は getcwd()）
	putenv('TESTMAN_DOCROOT='.$docroot);
	putenv('PHP_CLI_SERVER_WORKERS='.$workers);

	$child = pcntl_fork();

	if($child === -1){
		\cmdman\Std::println_danger('fork failed');
		\cmdman\Util::exit_error();
	}
	if($child === 0){
		// 端末から切り離して自身をプロセスグループのリーダーにする。
		// php -S のワーカーはこのグループに属するため、stop でグループごと止められる。
		posix_setsid();
		fclose(STDIN);
		fclose(STDOUT);
		fclose(STDERR);
		fopen('/dev/null','r');
		fopen($log,'a');
		fopen($log,'a');

		pcntl_exec(PHP_BINARY,['-S',$listen,$router]);
		exit(1);
	}
	// router は「同じ checkout のサーバか」の判定に使う（docroot は起動時の cwd 次第で揺れる）
	file_put_contents($file,json_encode(['pid'=>$child,'listen'=>$listen,'docroot'=>$docroot,'workers'=>$workers,'router'=>$router]));
	usleep(500000);

	if(null === $read($file)){
		\cmdman\Std::println_danger('Failed to start. See '.$log);
		\cmdman\Util::exit_error();
	}
	\cmdman\Std::println_info('Docroot: '.$docroot);
	\cmdman\Std::println_info('Log: '.$log);
	\cmdman\Std::println_success('Started (pid '.$child.'): http://'.$listen.'/');
};

/**
 * 起動中のサーバを停止する
 */
$stop = function(array $state): void{
	$pgid = (int)$state['pid'];

	// ワーカーはマスターと同じプロセスグループにいるため、グループごと止める。
	// マスターだけにSIGTERMを送るとワーカーが孤児として残りポートを掴み続ける。
	posix_kill($pgid * -1,SIGTERM);

	for($i=0;$i<50;$i++){
		usleep(100000);

		if(!posix_kill($pgid * -1,0)){
			break;
		}
	}
	if(posix_kill($pgid * -1,0)){
		posix_kill($pgid * -1,SIGKILL);
	}
	if(is_file($state['file'])){
		unlink($state['file']);
	}
	\cmdman\Std::println_success('Stopped (pid '.$pgid.'): http://'.$state['listen'].'/');
};

$listen = $host.':'.$port;

switch($sub){
	case 'start':
		$start($listen,$workers);
		return;

	case 'stop':
	case 'restart':
		$list = $targets($listen);

		if(sizeof($list) > 1){
			\cmdman\Std::println_danger('Multiple servers are running. Specify --port:');
			$print_list($list);
			\cmdman\Util::exit_error();
		}
		if(!empty($list)){
			$assert_owned($list[0]);
			$stop($list[0]);
		}else if($sub === 'stop'){
			\cmdman\Std::println_warning($specified ? ('Not running: http://'.$listen.'/') : ('Not running: '.$docroot));
		}
		if($sub === 'restart'){
			// 自動特定したサーバは、元の接続先とワーカー数のまま起動し直す
			empty($list)
				? $start($listen,$workers)
				: $start((string)$list[0]['listen'],(int)($list[0]['workers'] ?? $workers));
		}
		return;

	case 'status':
		$list = empty($all) ? $targets($listen) : $scan(null);

		if(empty($list)){
			\cmdman\Std::println_warning(
				!empty($all) ? 'No server is running' : ($specified ? ('Not running: http://'.$listen.'/') : ('Not running: '.$docroot))
			);
		}
		foreach($list as $state){
			\cmdman\Std::println_success('Running (pid '.$state['pid'].'): http://'.$state['listen'].'/');
			\cmdman\Std::println_info('   Docroot: '.$state['docroot']);
			\cmdman\Std::println_info('   Log: '.$log_path($state['file']));
		}
		return;
}
// サブコマンド無しは前面実行（Ctrl+Cで停止）
putenv('TESTMAN_DOCROOT='.$docroot);
putenv('PHP_CLI_SERVER_WORKERS='.$workers);

\cmdman\Std::println_info('Docroot: '.$docroot);
\cmdman\Std::println_info('Workers: '.$workers);
\cmdman\Std::println_success('http://'.$listen.'/');
\cmdman\Std::println('Press Ctrl+C to stop.');

passthru(
	escapeshellarg(PHP_BINARY).' -S '.escapeshellarg($listen).' '.escapeshellarg($router),
	$return_var
);
exit($return_var);
