<?php
$t = new \ebi\Template();
$src = '<rt:loop param="abc" var="a"><rt:loop param="abc" var="b">{$a}{$b}</rt:loop>-</rt:loop>';
$result = '1112-2122-';
$t->vars('abc',array(1,2));
eq($result,$t->get($src));
