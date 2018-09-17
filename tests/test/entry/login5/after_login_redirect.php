<?php

$b = b();

// ログイン
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->vars('after_login_redirect',\testman\Util::url('login5::aaa'));
$b->do_post('login5::login_url');
eq(200,$b->status());
eq(\testman\Util::url('login5::aaa'),$b->url());


// ログイン済みの場合でもafter_redirect
$b->vars('after_login_redirect',\testman\Util::url('login5::aaa'));
$b->do_post('login5::login_url');
eq(200,$b->status());
eq(\testman\Util::url('login5::aaa'),$b->url());


