<?php
$login_html =  <<< HTML
<html>
<body>
<form method="post">
<input type="text" name="user_name" />
<input type="password" name="password" />
<input type="submit" />
</form>
</body>
</html>
HTML;


$b = b();
$b->do_get(url('login_html::login'));
eq(401,$b->status());
eq(url('login_html::login'),$b->url());
eq($login_html,$b->body());

$b->vars('user','tokushima');
$b->vars('password','hogehoge');
$b->do_post(url('login_html::login'));
eq(200,$b->status());

eq(url('login_html::aaa'),$b->url());
eq(200,$b->status());
meq('{"result":{"abc":123}}',$b->body());

$b->do_post(url('login_html::logout'));
eq(200,$b->status());
eq(url('login_html::logout'),$b->url());
eq([],$b->json('result'));

$b->do_get(url('login_html::aaa'));
eq(401,$b->status());
eq(url('login_html::login'),$b->url());
meq($login_html,$b->body());

