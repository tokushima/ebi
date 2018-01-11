<?php
/**
 * session_lifetime = 1で定義されているはず
 * @var \testman\Browser $b
 */
$b = b();
$b->do_get('login1::login');
eq(401,$b->status());
eq(\testman\Util::url('login1::login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login1::login');
eq(200,$b->status());


$b->do_get('login1::aaa');
eq(200,$b->status());
eq('{"result":{"abc":123}}',$b->body());

sleep(2);

// session_lifetime over
$b->do_get('login1::aaa');
eq(401,$b->status());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

