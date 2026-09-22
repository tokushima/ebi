<?php
// app/Request: クラス型コンテナ #[Parameter] の構造検証。
// before → request_validation が request_validate_container で段数ぶん潜り、最内をクラスの #[Prop] で検証する。
// 検証エラーはレスポンスの error[] に、パス（rows[0].n / grid[0][n] 等）付きで表出する。

// --- Row[]（depth 1）---
// 正常: 各要素が Row の構造を満たす
$b = new \ebi\Browser();
$b->vars('rows', [['n' => 1, 'label' => 'a'], ['n' => 2]]);
$b->do_post(\testman\Util::url('index::request/rows'));
eq(true, $b->json('result/ok'));

// 要素で必須 n が欠落 → error に rows[0].n（段を潜って要素プロパティまで到達している証拠）
$b = new \ebi\Browser();
$b->vars('rows', [['label' => 'x']]);
$b->do_post(\testman\Util::url('index::request/rows'));
eq('rows[0].n', $b->json('error/0/group'));

// コンテナに配列でないスカラ → "rows must be an array"
$b = new \ebi\Browser();
$b->vars('rows', 'foo');
$b->do_post(\testman\Util::url('index::request/rows'));
eq('rows', $b->json('error/0/group'));
meq('array', $b->json('error/0/message'));

// --- Row[][]（depth 2）---
// 正常: 2段の入れ子で最内が Row
$b = new \ebi\Browser();
$b->vars('grid', [[['n' => 1]], [['n' => 2], ['n' => 3]]]);
$b->do_post(\testman\Util::url('index::request/grid'));
eq(true, $b->json('result/ok'));

// 段数が1段足りない（Row[] を渡す）→ 最内で構造検証に落ちる（grid[0][n]）
$b = new \ebi\Browser();
$b->vars('grid', [['n' => 1]]);
$b->do_post(\testman\Util::url('index::request/grid'));
eq('grid[0][n]', $b->json('error/0/group'));
meq('object of', $b->json('error/0/message'));
