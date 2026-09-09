<?php
/**
 * PHP built-in server (php -S) 用ルーターのスタブ。
 * 実体は ebi が管理する \ebi\Dt::serve_router()（先頭セグメント -> <entry>.php へ振り分け）。
 */
foreach ([__DIR__ . '/bootstrap.php', __DIR__ . '/vendor/autoload.php'] as $__f) {
	if (is_file($__f)) { require $__f; break; }
}
\ebi\Dt::serve_router(__DIR__);
