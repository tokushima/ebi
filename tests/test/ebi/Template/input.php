<?php
# textarea
$src = <<< 'PRE'
<form>
	<textarea name="hoge"></textarea>
</form>
PRE;
$t = new \ebi\Template();
eq($src,$t->get($src));


#select
$src = '<form><select name="abc" rt:param="abc"></select></form>';
$t = new \ebi\Template();
$t->vars("abc",array(123=>123,456=>456));
eq('<form><select name="abc"><option value="123">123</option><option value="456">456</option></select></form>',$t->get($src));


 #multiple
$src = '<form><input name="abc" type="checkbox" /></form>';
$t = new \ebi\Template();
eq('<form><input name="abc[]" type="checkbox" /></form>',$t->get($src));

$src = '<form><input name="abc" type="checkbox" rt:multiple="false" /></form>';
$t = new \ebi\Template();
eq('<form><input name="abc" type="checkbox" /></form>',$t->get($src));


#input_exception
$src = '<form rt:ref="true"><input type="text" name="hoge" /></form>';
$t = new \ebi\Template();
eq('<form><input type="text" name="hoge" value="" /></form>',$t->get($src));

$src = '<form rt:ref="true"><input type="password" name="hoge" /></form>';
$t = new \ebi\Template();
eq('<form><input type="password" name="hoge" value="" /></form>',$t->get($src));

$src = '<form rt:ref="true"><input type="hidden" name="hoge" /></form>';
$t = new \ebi\Template();
eq('<form><input type="hidden" name="hoge" value="" /></form>',$t->get($src));

$src = '<form rt:ref="true"><input type="checkbox" name="hoge" /></form>';
$t = new \ebi\Template();
eq('<form><input type="checkbox" name="hoge[]" /></form>',$t->get($src));

$src = '<form rt:ref="true"><input type="radio" name="hoge" /></form>';
$t = new \ebi\Template();
eq('<form><input type="radio" name="hoge" /></form>',$t->get($src));

$src = '<form rt:ref="true"><textarea name="hoge"></textarea></form>';
$t = new \ebi\Template();
eq('<form><textarea name="hoge"></textarea></form>',$t->get($src));

$src = '<form rt:ref="true"><select name="hoge"><option value="1">1</option><option value="2">2</option></select></form>';
$t = new \ebi\Template();
eq('<form><select name="hoge"><option value="1">1</option><option value="2">2</option></select></form>',$t->get($src));


#html5
$src = <<< 'PRE'
<form rt:ref="true">
	<input type="search" name="search" />
	<input type="tel" name="tel" />
	<input type="url" name="url" />
	<input type="email" name="email" />
	<input type="datetime" name="datetime" />
	<input type="datetime-local" name="datetime_local" />
	<input type="date" name="date" />
	<input type="month" name="month" />
	<input type="week" name="week" />
	<input type="time" name="time" />
	<input type="number" name="number" />
	<input type="range" name="range" />
	<input type="color" name="color" />
</form>
PRE;

$rslt = <<< 'PRE'
<form>
	<input type="search" name="search" value="hoge" />
	<input type="tel" name="tel" value="000-000-0000" />
	<input type="url" name="url" value="http://tokushimakazutaka.com" />
	<input type="email" name="email" value="hoge@hoge.hoge" />
	<input type="datetime" name="datetime" value="1970-01-01T00:00:00.0Z" />
	<input type="datetime-local" name="datetime_local" value="1970-01-01T00:00:00.0Z" />
	<input type="date" name="date" value="1970-01-01" />
	<input type="month" name="month" value="1970-01" />
	<input type="week" name="week" value="1970-W15" />
	<input type="time" name="time" value="12:30" />
	<input type="number" name="number" value="1234" />
	<input type="range" name="range" value="7" />
	<input type="color" name="color" value="#ff0000" />
</form>
PRE;
$t = new \ebi\Template();
$t->vars("search","hoge");
$t->vars("tel","000-000-0000");
$t->vars("url","http://tokushimakazutaka.com");
$t->vars("email","hoge@hoge.hoge");
$t->vars("datetime","1970-01-01T00:00:00.0Z");
$t->vars("datetime_local","1970-01-01T00:00:00.0Z");
$t->vars("date","1970-01-01");
$t->vars("month","1970-01");
$t->vars("week","1970-W15");
$t->vars("time","12:30");
$t->vars("number","1234");
$t->vars("range","7");
$t->vars("color","#ff0000");

eq($rslt,$t->get($src));

