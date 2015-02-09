<?php
$b = new \testman\Browser();
$b->do_get(url('test_login1::notype'));
eq(401,$b->status());
eq(url('test_login1::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","group":"","type":"UnauthorizedException"}]}',$b->body());

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post(url('test_login1::login'));
eq(200,$b->status());

eq(url('test_login1::notype'),$b->url());
eq(200,$b->status());
meq('{"result":{"abc":123}}',$b->body());

$b->do_post(url('test_login1::logout'));
eq(200,$b->status());
eq(url('test_login1::logout'),$b->url());
eq('{"result":{"login":false}}',$b->body());

$b->do_get(url('test_login1::notype'));
eq(401,$b->status());
eq(url('test_login1::login'),$b->url());
meq('{"error":[{"message":"Unauthorized","group":"","type":"UnauthorizedException"}]}',$b->body());

