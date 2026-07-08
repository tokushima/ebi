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

$src = '<rt:if param="abc">aa<rt:else />bb</rt:if>';
$result = 'bb';
$t = new \ebi\Template();
eq($result,$t->get($src));


// rt:elseif ---------------------------------------------------------------

// 最初の分岐が真
$src = '<rt:if param="a">A<rt:elseif param="b" />B<rt:elseif param="c" />C<rt:else />D</rt:if>';
$t = new \ebi\Template();
$t->vars("a",true); $t->vars("b",false); $t->vars("c",false);
eq('A',$t->get($src));

// 2番目(elseif)が真
$src = '<rt:if param="a">A<rt:elseif param="b" />B<rt:elseif param="c" />C<rt:else />D</rt:if>';
$t = new \ebi\Template();
$t->vars("a",false); $t->vars("b",true); $t->vars("c",false);
eq('B',$t->get($src));

// 3番目(2つ目のelseif)が真
$src = '<rt:if param="a">A<rt:elseif param="b" />B<rt:elseif param="c" />C<rt:else />D</rt:if>';
$t = new \ebi\Template();
$t->vars("a",false); $t->vars("b",false); $t->vars("c",true);
eq('C',$t->get($src));

// どれも偽 → else
$src = '<rt:if param="a">A<rt:elseif param="b" />B<rt:elseif param="c" />C<rt:else />D</rt:if>';
$t = new \ebi\Template();
$t->vars("a",false); $t->vars("b",false); $t->vars("c",false);
eq('D',$t->get($src));

// elseifが先に真なら後続の分岐は選ばれない
$src = '<rt:if param="a">A<rt:elseif param="b" />B<rt:elseif param="c" />C</rt:if>';
$t = new \ebi\Template();
$t->vars("a",false); $t->vars("b",true); $t->vars("c",true);
eq('B',$t->get($src));

// elseのみ無し・どれも偽 → 空
$src = '<rt:if param="a">A<rt:elseif param="b" />B</rt:if>';
$t = new \ebi\Template();
$t->vars("a",false); $t->vars("b",false);
eq('',$t->get($src));

// 比較式でのelseif
$src = '<rt:if param="{$n==1}">one<rt:elseif param="{$n==2}" />two<rt:elseif param="{$n==3}" />three<rt:else />other</rt:if>';
$t = new \ebi\Template(); $t->vars("n",1); eq('one',$t->get($src));
$t = new \ebi\Template(); $t->vars("n",2); eq('two',$t->get($src));
$t = new \ebi\Template(); $t->vars("n",3); eq('three',$t->get($src));
$t = new \ebi\Template(); $t->vars("n",9); eq('other',$t->get($src));

// ネストしたrt:if内のelseif（外側elseの中）
$src = '<rt:if param="a">A<rt:else /><rt:if param="b">B<rt:elseif param="c" />C<rt:else />D</rt:if></rt:if>';
$t = new \ebi\Template(); $t->vars("a",true); $t->vars("b",false); $t->vars("c",false); eq('A',$t->get($src));
$t = new \ebi\Template(); $t->vars("a",false); $t->vars("b",true); $t->vars("c",false); eq('B',$t->get($src));
$t = new \ebi\Template(); $t->vars("a",false); $t->vars("b",false); $t->vars("c",true); eq('C',$t->get($src));
$t = new \ebi\Template(); $t->vars("a",false); $t->vars("b",false); $t->vars("c",false); eq('D',$t->get($src));

// ネストしたrt:if内のelseif（外側elseifの中／3階層）
$src = '<rt:if param="{$x==1}">L1<rt:elseif param="{$x==2}" /><rt:if param="{$y==1}">Y1<rt:elseif param="{$y==2}" />Y2<rt:else />Yn</rt:if><rt:else />Le</rt:if>';
$t = new \ebi\Template(); $t->vars("x",1); $t->vars("y",0); eq('L1',$t->get($src));
$t = new \ebi\Template(); $t->vars("x",2); $t->vars("y",1); eq('Y1',$t->get($src));
$t = new \ebi\Template(); $t->vars("x",2); $t->vars("y",2); eq('Y2',$t->get($src));
$t = new \ebi\Template(); $t->vars("x",2); $t->vars("y",9); eq('Yn',$t->get($src));
$t = new \ebi\Template(); $t->vars("x",9); $t->vars("y",0); eq('Le',$t->get($src));

// rt:loop内のif/elseif/else
$src = '<rt:loop param="{$list}" var="v">[<rt:if param="{$v==1}">one<rt:elseif param="{$v==2}" />two<rt:else />x</rt:if>]</rt:loop>';
$t = new \ebi\Template(); $t->vars("list",[1,2,3]);
eq('[one][two][x]',$t->get($src));

