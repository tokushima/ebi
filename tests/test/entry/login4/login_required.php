<?php
/**
 * login要求のaction
*/
$b = b();

$b->do_get('login4::aaa');
eq(401,$b->status());
eq(\testman\Util::url('login4::aaa'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login4::login');
eq(200,$b->status());

$b->do_post('login4::aaa');
eq(200,$b->status());

sleep(2);

// セッションが切れてもログイン状態が復活している
$b->do_post('login4::aaa');
eq(200,$b->status());






