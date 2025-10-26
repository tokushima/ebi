<?php
$b = b();

// loginへリダイレクトされる
$b->do_get('login1::notype');
eq(401,$b->status());
eq(\testman\Util::url('login1::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

// ログインするとlogged_in_afterに従いリダイレクトする
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login1::login');
eq(200,$b->status());
eq(\testman\Util::url('login1::aaa'),$b->url());


// ログインしてればリダイレクトしない
$b->do_get('login1::notype');
eq(\testman\Util::url('login1::notype'),$b->url());
eq(200,$b->status());
meq('{"result":{"abc":123}}',$b->body());


// ログアウト
$b->do_post('login1::logout');
eq(200,$b->status());
eq(\testman\Util::url('login1::logout'),$b->url());
eq([],$b->json('result'));


// ログアウトしたのでまたログインへ
$b->do_get('login1::notype');
eq(401,$b->status());
eq(\testman\Util::url('login1::login'),$b->url());
meq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

