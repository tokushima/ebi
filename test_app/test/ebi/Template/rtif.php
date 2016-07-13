<?php

$src = '<rt:if param="abc">hoge</rt:if>';
$result = 'hoge';
$t = new \ebi\Template();
$t->vars("abc",true);
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'aa';
$t = new \ebi\Template();
$t->vars("abc",array(1));
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'bb';
$t = new \ebi\Template();
$t->vars("abc",[]);
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'aa';
$t = new \ebi\Template();
$t->vars("abc",true);
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'bb';
$t = new \ebi\Template();
$t->vars("abc",false);
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'aa';
$t = new \ebi\Template();
$t->vars("abc","a");
eq($result,$t->get($src));

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'bb';
$t = new \ebi\Template();
$t->vars("abc","");
eq($result,$t->get($src));

