<?php
$t = new \ebi\Template();
$src = '<rt:loop param="abc" evenodd="evenodd" counter="counter">{$counter}[{$evenodd}]</rt:loop>';
$result = '1[odd]2[even]3[odd]4[even]5[odd]6[even]';
$t->vars('abc',array(1,2,3,4,5,6));
eq($result,$t->get($src));

