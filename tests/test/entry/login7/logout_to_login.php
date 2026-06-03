<?php
/**
 * bare logout アクション（do_logoutではない）でも、
 * ログイン後にlogoutに戻らない
 */
$b = b();

// ログアウト（未ログイン）→ login にリダイレクト + 401
$b->do_post('login7::logout');
eq(401,$b->status());
eq(\testman\Util::url('login7::login'),$b->url());

// ログイン → logout に戻らず login のまま
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login7::login');
eq(200,$b->status());
eq(\testman\Util::url('login7::login'),$b->url());
