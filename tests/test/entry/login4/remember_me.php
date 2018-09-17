<?php
/**
 * remember_me
*/
$b = b();

// ログインしてなければ例外 (plugin)
$b->do_get('login4::aaa');
eq(401,$b->status());
eq(\testman\Util::url('login4::aaa'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

// ログイン
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login4::login');
eq(200,$b->status());

// 正常
$b->do_post('login4::aaa');
eq(200,$b->status());

// セッションを全て消す
\ebi\SessionDao::find_delete();


// \test\flow\plugin\Login4::remember_meによりセッションが切れてもログイン状態が復活している
$b->do_post('login4::aaa');
eq(200,$b->status());

// ログアウト
$b->do_post('login4::logout');

// ログアウトするとreadmeからもログアウト \test\flow\plugin\Login4::before_logout
$b->do_post('login4::aaa');
eq(401,$b->status());



