<?php
$b = new \testman\Browser();
$b->do_get(url('test_login::login'));
eq(401,$b->status());
eq(url('test_login::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","group":"","type":"UnauthorizedException"}]}',$b->body());

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post(url('test_login::login'));
eq(200,$b->status());

eq(url('test_login::aaa'),$b->url());
eq(200,$b->status());
eq('{"result":{"abc":123}}',$b->body());

$b->do_post(url('test_login::logout'));
eq(200,$b->status());
eq(url('test_login::logout'),$b->url());
eq('{"result":{"login":false}}',$b->body());

$b->do_get(url('test_login::aaa'));
eq(401,$b->status());
eq(url('test_login::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","group":"","type":"UnauthorizedException"}]}',$b->body());

