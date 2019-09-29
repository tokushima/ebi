<?php
$b = b();

// loginへリダイレクトされる
$b->do_get('login1::othertype');
eq(401,$b->status());
eq(\testman\Util::url('login1::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

// ログインするとlogged_in_afterに従いリダイレクトする
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login1::login');
eq(200,$b->status());
eq(\testman\Util::url('login1::aaa'),$b->url());


// ログインしているがユーザータイプが違うのでエラー
$b->do_get('login1::othertype');
eq(\testman\Util::url('login1::othertype'),$b->url());
eq(401,$b->status());
$b->has_error('UnauthorizedException');

