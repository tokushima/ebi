<?php
/**
 * remember me
 */
$b = b();

// remember_meでログインされる
$b->do_get(url('login4::aaa'));
eq(200,$b->status());

eq(url('login4::aaa'),$b->url());
eq('{"result":{"abc":123}}',$b->body());

