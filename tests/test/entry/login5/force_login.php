<?php
/**
 * pluginを使わずログインさせる
 */
$b = b();

$b->vars('url',\testman\Util::url('login5::aaa'));
$b->do_get(\testman\Util::url('login5::force_login'));

eq(\testman\Util::url('login5::aaa'),$b->url());
eq('{"result":{"abc":123}}',$b->body());


