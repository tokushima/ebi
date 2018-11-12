<?php
$t = new \ebi\Template();

$src = <<< 'TEXT'
<rt:loop param="abc" counter="loop_counter" key="loop_key" var="loop_var">
{$loop_counter}: {$loop_key} => {$loop_var}
</rt:loop>
hoge
TEXT;

$result = <<< 'TEXT'
1: A => 456
2: B => 789
3: C => 010
4: D => 999
hoge
TEXT;

$t = new \ebi\Template();
$t->vars("abc",array("A"=>"456","B"=>"789","C"=>"010","D"=>"999"));
eq($result,$t->get($src));

////////////////////////////////////////////

$t = new \ebi\Template();

$src = <<< 'TEXT'
<rt:loop param="abc" counter="loop_counter" key="loop_key" var="loop_var" limit="2">
{$loop_counter}: {$loop_key} => {$loop_var}
</rt:loop>
hoge
TEXT;

$result = <<< 'TEXT'
1: A => 456
2: B => 789
hoge
TEXT;
