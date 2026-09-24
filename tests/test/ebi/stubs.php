<?php
// 同梱している IDE stub が、ツール本体が出力するものとずれていないことを確認する。
//
// stub は各ツールの `--stub` が出力する生成物なので、手で編集せず再生成する:
//   cmdman  --stub > resources/stubs/cmdman.stub.php
//   testman --stub > resources/stubs/testman.stub.php
//
// ツールが未インストール、または --stub 非対応の古いバージョンの場合は検証をスキップする
// （CI やツールを入れていない環境で落とさないため）。

$root = dirname(__DIR__, 3);

$check = function(string $tool, string $path) use($root): void{
	$bin = trim((string)shell_exec('command -v '.escapeshellarg($tool).' 2>/dev/null'));

	if($bin === ''){
		return; // 未インストール
	}
	$generated = shell_exec(escapeshellarg($bin).' --stub 2>/dev/null');

	if(!is_string($generated) || strncmp($generated, '<?php', 5) !== 0){
		return; // --stub 非対応
	}
	$normalize = fn(string $s): array => explode("\n", rtrim(str_replace("\r\n", "\n", $s)));
	$actual = $normalize($generated);
	$bundled = $normalize((string)file_get_contents($root.'/'.$path));

	// 差分は先頭3件だけを出す（stub 全文を突き合わせると読めないため）
	$diff = [];
	foreach(array_keys($actual + $bundled) as $i){
		if(($actual[$i] ?? null) !== ($bundled[$i] ?? null)){
			$diff[] = sprintf('L%d: 本体[%s] 同梱[%s]', $i + 1, $actual[$i] ?? '(無し)', $bundled[$i] ?? '(無し)');

			if(count($diff) >= 3){
				break;
			}
		}
	}
	eq('', implode(' / ', $diff), $path.' がツール本体とずれています。再生成してください: '.$tool.' --stub > '.$path);
};

$check('cmdman', 'resources/stubs/cmdman.stub.php');
$check('testman', 'resources/stubs/testman.stub.php');
