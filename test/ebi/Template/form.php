<?php
$src = <<< 'PRE'
<form rt:ref="true" rt:param="data">
	<input type="text" name="aaa" />
</form>
PRE;

$result = <<< 'PRE'
<form>
	<input type="text" name="aaa" value="hogehoge" />
</form>
PRE;

$t = new \ebi\Template();
$t->vars("data",array("aaa"=>"hogehoge"));
eq($result,$t->get($src));


#input
$src = <<< 'PRE'
<form rt:ref="true">
	<input type="text" name="aaa" />
	<input type="text" name="ttt" />
	<input type="checkbox" name="bbb" value="hoge" />hoge
	<input type="checkbox" name="bbb" value="fuga" checked="checked" />fuga
	<input type="checkbox" name="eee" value="true" checked />foo
	<input type="checkbox" name="fff" value="false" />foo
	<input type="submit" />
	<textarea name="aaa"></textarea>
	<textarea name="ttt"></textarea>
	
	<select name="ddd" size="5" multiple>
	<option value="123" selected="selected">123</option>
	<option value="456">456</option>
	<option value="789" selected>789</option>
	</select>
	<select name="XYZ" rt:param="xyz"></select>
</form>
PRE;
$result = <<< 'PRE'
<form>
	<input type="text" name="aaa" value="hogehoge" />
	<input type="text" name="ttt" value="&lt;tag&gt;ttt&lt;/tag&gt;" />
	<input type="checkbox" name="bbb[]" value="hoge" checked="checked" />hoge
	<input type="checkbox" name="bbb[]" value="fuga" />fuga
	<input type="checkbox" name="eee[]" value="true" checked="checked" />foo
	<input type="checkbox" name="fff[]" value="false" checked="checked" />foo
	<input type="submit" />
	<textarea name="aaa">hogehoge</textarea>
	<textarea name="ttt">&lt;tag&gt;ttt&lt;/tag&gt;</textarea>
	
	<select name="ddd[]" size="5" multiple="multiple">
	<option value="123">123</option>
	<option value="456" selected="selected">456</option>
	<option value="789" selected="selected">789</option>
	</select>
	<select name="XYZ"><option value="A">456</option><option value="B" selected="selected">789</option><option value="C">010</option></select>
</form>
PRE;

$t = new \ebi\Template();
$t->vars("aaa","hogehoge");
$t->vars("ttt","<tag>ttt</tag>");
$t->vars("bbb","hoge");
$t->vars("XYZ","B");
$t->vars("xyz",array("A"=>"456","B"=>"789","C"=>"010"));
$t->vars("ddd",array("456","789"));
$t->vars("eee",true);
$t->vars("fff",false);
eq($result,$t->get($src));

$src = <<< 'PRE'
<form rt:ref="true">
	<select name="ddd" rt:param="abc">
	</select>
</form>
PRE;
$result = <<< 'PRE'
<form>
	<select name="ddd"><option value="123">123</option><option value="456" selected="selected">456</option><option value="789">789</option></select>
</form>
PRE;
$t = new \ebi\Template();
$t->vars("abc",array(123=>123,456=>456,789=>789));
$t->vars("ddd","456");
eq($result,$t->get($src));

$src = <<< 'PRE'
<form rt:ref="true">
<rt:loop param="abc" var="v">
	<input type="checkbox" name="ddd" value="{$v}" />
</rt:loop>
</form>
PRE;
$result = <<< 'PRE'
<form>
	<input type="checkbox" name="ddd[]" value="123" />
	<input type="checkbox" name="ddd[]" value="456" checked="checked" />
	<input type="checkbox" name="ddd[]" value="789" />
</form>
PRE;
$t = new \ebi\Template();
$t->vars("abc",array(123=>123,456=>456,789=>789));
$t->vars("ddd","456");
eq($result,$t->get($src));

