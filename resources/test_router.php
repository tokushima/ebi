<?php
/**
 * PHP built-in server (php -S) 用ルーターのスタブ。
 * 実体は ebi が管理する \ebi\Dt::serve_router()（先頭セグメント -> <entry>.php へ振り分け）。
 *
 * docroot(エントリ .php と autoload の基準)は次の優先順で解決する:
 *   1. 環境変数 TESTMAN_DOCROOT  … testman --serve が vendor 実体を直接起動する際に注入
 *   2. __DIR__                    … 従来どおりアプリの tests 直下へコピーして使う場合
 * これにより vendor の本ファイルを直接 php -S ルーターに指定でき、
 * プロジェクト側へスタブをコピーしなくても動く（コピー運用も無改修で維持）。
 */
$__root = getenv('TESTMAN_DOCROOT') ?: __DIR__;
foreach ([$__root . '/bootstrap.php', $__root . '/vendor/autoload.php'] as $__f) {
	if (is_file($__f)) { require $__f; break; }
}
\ebi\Dt::serve_router($__root);
