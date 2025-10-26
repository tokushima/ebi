<?php
$b = b();

// automapにdo_loginが含まれるのでリダイレクトされる
$b->do_get('login2::automap_action/aaa');
eq(401,$b->status());
eq(\testman\Util::url('login2::automap_action/do_login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

// ログイン
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login2::automap_action/do_login');
eq(200,$b->status());

// ログイン済みなので正常
eq(\testman\Util::url('login2::automap_action/aaa'),$b->url());
eq(200,$b->status());
eq('{"result":{"abc":123}}',$b->body());

// ログアウト
$b->do_post('login2::automap_action/do_logout');
eq(200,$b->status());
eq(\testman\Util::url('login2::automap_action/do_logout'),$b->url());
eq([],$b->json('result'));

// ログアウトしたのでdo_loginにリダイレクト
$b->do_get('login2::automap_action/aaa');
eq(401,$b->status());
eq(\testman\Util::url('login2::automap_action/do_login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

