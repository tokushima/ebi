<?php
/**
 * Accept: application/json で未ログインで logout を叩くと
 * リダイレクトせず 200 を返す（do_logout の場合）
 */
$b = b();
$b->header('Accept','application/json');

// do_logout（login3 の URL名 'logout' は do_logout にマップ）
$b->do_post('login3::logout');
eq(200,$b->status());
eq(\testman\Util::url('login3::logout'),$b->url());

// bare logout でも同じ
$b = b();
$b->header('Accept','application/json');
$b->do_post('login7::logout');
eq(200,$b->status());
eq(\testman\Util::url('login7::logout'),$b->url());
