<?php
$b = new \testman\Browser();
$b->do_get(test_map_url('test_login::login'));
eq(401,$b->status());
eq(test_map_url('test_login::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","group":"","type":"UnauthorizedException"}]}',$b->body());

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post(test_map_url('test_login::login'));
eq(200,$b->status());

eq(test_map_url('test_login::aaa'),$b->url());
eq(200,$b->status());
meq('{"result":{"abc":123}}',$b->body());

$b->do_post(test_map_url('test_login::logout'));
eq(200,$b->status());
eq(test_map_url('test_login::logout'),$b->url());
eq('{"result":{"login":false}}',$b->body());

$b->do_get(test_map_url('test_login::aaa'));
eq(401,$b->status());
eq(test_map_url('test_login::login'),$b->url());
meq('{"error":[{"message":"Unauthorized","group":"","type":"UnauthorizedException"}]}',$b->body());

