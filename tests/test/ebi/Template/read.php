<?php
$src = 'abc {$abc} def {$def} ghi {$ghi}';
$result = 'abc 123 def 456 ghi 789';
$t = new \ebi\Template();
$t->vars("abc",123);
$t->vars("def",456);
$t->vars("ghi",789);
eq($result,$t->get($src));
