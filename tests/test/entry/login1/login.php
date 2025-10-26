<?php
$b = b();

// ログイン失敗
$b->do_get('login1::login');
eq(401,$b->status());
eq(\testman\Util::url('login1::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

// ログイン
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login1::login');
eq(200,$b->status());


// 正常
eq(\testman\Util::url('login1::aaa'),$b->url());
eq(200,$b->status());
eq('{"result":{"abc":123}}',$b->body());

// ログアウト
$b->do_post('login1::logout');
eq(200,$b->status());
eq(\testman\Util::url('login1::logout'),$b->url());
eq([],$b->json('result'));

// ログインしていない
$b->do_get('login1::aaa');
eq(401,$b->status());
eq(\testman\Util::url('login1::login'),$b->url());
$b->has_error('UnauthorizedException');



