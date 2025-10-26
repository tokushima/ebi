<?php

$b = b();

// ログイン
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login6::action6/do_login');
eq(200,$b->status());

// 正常
$b->do_get('login6::action6/aaa');
eq(200,$b->status());
eq('{"result":{"abc":123}}',$b->body());


$b = b();
$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post('login6::fake_login');
eq(200,$b->status());

// ユーザモデルが違う
$b->do_get('login6::action6/aaa');
eq(403,$b->status());
