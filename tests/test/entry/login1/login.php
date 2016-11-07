<?php
$b = b();
$b->do_get(url('login1::login'));
eq(401,$b->status());
eq(url('login1::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post(url('login1::login'));
eq(200,$b->status());

eq(url('login1::aaa'),$b->url());
eq(200,$b->status());
eq('{"result":{"abc":123}}',$b->body());

$b->do_post(url('login1::logout'));
eq(200,$b->status());
eq(url('login1::logout'),$b->url());
eq([],$b->json('result'));

$b->do_get(url('login1::aaa'));
eq(401,$b->status());
eq(url('login1::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());


// ログインしてなければ401
$b->do_post(url('login1::not_user_perm'));
eq(401,$b->status());

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post(url('login1::login'));
eq(200,$b->status());

// ログインしていればエラー
$b->do_post(url('login1::not_user_perm'));
eq(200,$b->status());
eq('{"error":[{"message":"not permitted","type":"NotPermittedException"}]}',$b->body());

