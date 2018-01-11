<?php
$b = b();
$b->do_get('login2::automap/aaa');
eq(401,$b->status());
eq(\testman\Util::url('login2::automap/do_login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());


$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login2::automap/do_login');
eq(200,$b->status());

eq(\testman\Util::url('login2::automap/aaa'),$b->url());
eq(200,$b->status());
eq('{"result":{"abc":123}}',$b->body());

$b->do_post('login2::automap/do_logout');
eq(200,$b->status());
eq(\testman\Util::url('login2::automap/do_logout'),$b->url());
eq([],$b->json('result'));

$b->do_get('login2::automap/aaa');
eq(401,$b->status());
eq(\testman\Util::url('login2::automap/do_login'),$b->url());
eq('{"error":[{"message":"Unauthorized","type":"UnauthorizedException"}]}',$b->body());

