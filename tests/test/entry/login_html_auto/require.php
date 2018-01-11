<?php

$b = b();
$b->do_get('login_html_auto::temp1');
eq(401,$b->status());
eq(\testman\Util::url('login_html_auto::login'),$b->url());

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login_html_auto::login');
eq(200,$b->status());

eq(\testman\Util::url('login_html_auto::temp1'),$b->url());
eq(200,$b->status());




$b->do_post('login_html_auto::logout');
eq(401,$b->status());
eq(\testman\Util::url('login_html_auto::login'),$b->url());


$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login_html_auto::login');
eq(200,$b->status());

$b->do_get('login_html_auto::temp2');
eq(200,$b->status());
eq(\testman\Util::url('login_html_auto::temp2'),$b->url());

