<?php
/**
 * ログアウトからのログインしてもログアウトにリダイレクトしない
 */
$b = b();

// ログアウト
$b->do_post('login3::logout');
eq(401,$b->status());
eq(\testman\Util::url('login3::login'),$b->url());

// ログイン
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login3::login');
eq(200,$b->status());

eq(\testman\Util::url('login3::login'),$b->url());







