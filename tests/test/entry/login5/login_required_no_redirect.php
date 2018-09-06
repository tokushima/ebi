<?php
/**
 * name=loginがないとログインアクションにリダイレクトされない
 * @var \testman\Browser $b
 */
$b = b();

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login5::login_abc');

$b->do_get(\testman\Util::url('login5::aaa'));
eq(200,$b->status());
eq('{"result":{"abc":123}}',$b->body());



