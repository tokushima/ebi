<?php
#reform
$src = <<< 'PRE'
<form rt:aref="true">
	<input type="text" name="{$aaa_name}" />
	<input type="checkbox" name="{$bbb_name}" value="hoge" />hoge
	<input type="checkbox" name="{$bbb_name}" value="fuga" checked="checked" />fuga
	<input type="checkbox" name="{$eee_name}" value="true" checked />foo
	<input type="checkbox" name="{$fff_name}" value="false" />foo
	<input type="submit" />
	<textarea name="{$aaa_name}"></textarea>
	
	<select name="{$ddd_name}" size="5" multiple>
	<option value="123" selected="selected">123</option>
	<option value="456">456</option>
	<option value="789" selected>789</option>
	</select>
	<select name="{$XYZ_name}" rt:param="xyz"></select>
</form>
PRE;
$result = <<< 'PRE'
<form>
	<input type="text" name="aaa" value="hogehoge" />
	<input type="checkbox" name="bbb[]" value="hoge" checked="checked" />hoge
	<input type="checkbox" name="bbb[]" value="fuga" />fuga
	<input type="checkbox" name="eee[]" value="true" checked="checked" />foo
	<input type="checkbox" name="fff[]" value="false" checked="checked" />foo
	<input type="submit" />
	<textarea name="aaa">hogehoge</textarea>
	
	<select name="ddd[]" size="5" multiple="multiple">
	<option value="123">123</option>
	<option value="456" selected="selected">456</option>
	<option value="789" selected="selected">789</option>
	</select>
	<select name="XYZ"><option value="A">456</option><option value="B" selected="selected">789</option><option value="C">010</option></select>
</form>
PRE;
$t = new \ebi\Template();
$t->vars("aaa_name","aaa");
$t->vars("bbb_name","bbb");
$t->vars("XYZ_name","XYZ");
$t->vars("xyz_name","xyz");
$t->vars("ddd_name","ddd");
$t->vars("eee_name","eee");
$t->vars("fff_name","fff");

$t->vars("aaa","hogehoge");
$t->vars("bbb","hoge");
$t->vars("XYZ","B");
$t->vars("xyz",array("A"=>"456","B"=>"789","C"=>"010"));
$t->vars("ddd",array("456","789"));
$t->vars("eee",true);
$t->vars("fff",false);
eq($result,$t->get($src));

