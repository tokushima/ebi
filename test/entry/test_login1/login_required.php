<?php
/**
 * login要求のaction
 */
$b = new \testman\Browser();

// loginにリダイレクトされる
$b->do_get(url('test_login1::bbb'));
eq(401,$b->status());
eq(url('test_login1::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","group":"","type":"UnauthorizedException"}]}',$b->body());

// ログインしたらbbbに戻る
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post(url('test_login1::login'));
eq(url('test_login1::bbb'),$b->url());
eq(200,$b->status());

// ログイン済みならリダイレクトされない
eq(url('test_login1::bbb'),$b->url());
eq(200,$b->status());
meq('{"result":{"abc":123}}',$b->body());

// ログアウト
$b->do_get(url('test_login1::logout'));

// 最初からログインならlogin_redirectに従う
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post(url('test_login1::login'));
eq(url('test_login1::aaa'),$b->url());
eq(200,$b->status());

