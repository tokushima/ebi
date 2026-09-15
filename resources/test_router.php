<?php
/**
 * PHP built-in server (php -S) 用ルーターのスタブ。
 * 実体は ebi が管理する \ebi\Dt::serve_router()（先頭セグメント -> <entry>.php へ振り分け）。
 *
 * docroot(エントリ .php と autoload の基準)の解決:
 *   1. 環境変数 TESTMAN_DOCROOT … testman --serve が起動時に注入する
 *   2. getcwd()                 … 手動起動時。実行したディレクトリ(アプリ直下)を docroot とする
 *      例: cd tests && php -S localhost:8888 vendor/tokushima/ebi/resources/test_router.php
 * vendor 同梱の本ファイルを直接指定して使う想定（プロジェクトへコピーしない）。
 */
$__root = getenv('TESTMAN_DOCROOT') ?: getcwd();
foreach ([$__root . '/bootstrap.php', $__root . '/vendor/autoload.php'] as $__f) {
	if (is_file($__f)) { require $__f; break; }
}
\ebi\Dt::serve_router($__root);
