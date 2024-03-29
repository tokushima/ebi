<?php
/**
 * login要求のaction
 */
$b = b();

// loginにリダイレクトされる
$b->do_get('login1::bbb');
eq(\testman\Util::url('login1::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

// ログインしたらlogged_in_afterに従う
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login1::login');
eq(\testman\Util::url('login1::aaa'),$b->url());
eq(200,$b->status());


// ログイン済みならリダイレクトされない
$b->do_get('login1::bbb');
eq(\testman\Util::url('login1::bbb'),$b->url());
eq(200,$b->status());
meq('{"result":{"abc":123}}',$b->body());


// ログアウト
$b->do_get('login1::logout');

// 最初からログインならlogged_in_afterに従う
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login1::login');
eq(\testman\Util::url('login1::aaa'),$b->url());
eq(200,$b->status());

