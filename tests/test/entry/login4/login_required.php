<?php
/**
 * remember me
 */
$b = b();


// loginにリダイレクトされる
$b->do_get(url('login4::aaa'));
eq(200,$b->status());
eq(url('login4::aaa'),$b->url());

eq(200,$b->status());
meq('{"result":{"abc":123}}',$b->body());

