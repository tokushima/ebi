<?php

$b = b();

// ログインしてなくてもリダイレクトしない
// unauthorized_redirect = false
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->vars('after_login_redirect',\testman\Util::url('login5::aaa'));
$b->do_post('login5::aaa');
eq(401,$b->status());
eq(\testman\Util::url('login5::aaa'),$b->url());



