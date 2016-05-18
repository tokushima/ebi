<?php
/**
 * login要求のaction
 */
$b = b();

// loginにリダイレクトされる
$b->do_get(url('test_login3::aaa'));
eq(401,$b->status());
eq(url('test_login3::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

// ログインしたらaaaに戻る
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post(url('test_login3::login'));
eq(url('test_login3::aaa'),$b->url());
eq(200,$b->status());


// ログイン済みならリダイレクトされない
$b->do_get(url('test_login3::aaa'));
eq(url('test_login3::aaa'),$b->url());
eq(200,$b->status());
meq('{"result":{"abc":123}}',$b->body());

