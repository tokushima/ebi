<?php
$obj = new \test\xml\Xml();

$self = new \ebi\Xml('abc',$obj);
eq('<abc><aaa>hoge</aaa><ccc>123</ccc></abc>',$self->get());

$n = explode('\\',get_class($obj));
$n = array_pop($n);


$obj1 = clone($obj);
$obj2 = clone($obj);
$obj3 = clone($obj);
$obj2->ccc(456);
$obj3->ccc(789);
$arr = array($obj1,$obj2,$obj3);
$self = new \ebi\Xml('abc',$arr);
eq(
		sprintf('<abc>'
				.'<%s><aaa>hoge</aaa><ccc>123</ccc></%s>'
				.'<%s><aaa>hoge</aaa><ccc>456</ccc></%s>'
				.'<%s><aaa>hoge</aaa><ccc>789</ccc></%s>'
				.'</abc>',
				$n,$n,$n,$n,$n,$n
		),$self->get());



$obj = new \ebi\Request();
$obj->rm_vars();
$obj->vars('aaa','hoge');
$obj->vars('ccc',123);
$self = new \ebi\Xml('abc',$obj);
eq('<abc><aaa>hoge</aaa><ccc>123</ccc></abc>',$self->get());



$src = "<tag><abc><def var='123'><ghi selected>hoge</ghi></def></abc></tag>";
$tag = \ebi\Xml::extract($src,'tag');
eq("hoge",$tag->find_get("abc/def/ghi")->value());
eq("123",$tag->find_get("abc/def")->in_attr('var'));
eq("selected",$tag->find_get("abc/def/ghi")->in_attr('selected'));
eq("<def var='123'><ghi selected>hoge</ghi></def>",$tag->find_get("abc/def")->plain());

try{
	$tag->find_get("abc/def/xyz");
	fail();
}catch(\ebi\exception\NotFoundException $e){
}

$src = <<< 'PRE'
<tag>
<abc>
<def var="123">
<ghi selected>hoge</ghi>
<ghi>
<jkl>rails</jkl>
</ghi>
<ghi ab="true">django</ghi>
</def>
</abc>
</tag>
PRE;
$tag = \ebi\Xml::extract($src,"tag");
eq("django",$tag->find_get("abc/def/ghi",2)->value());
eq("rails",$tag->find_get("abc/def/ghi",1)->find_get('jkl')->value());

eq("123",$tag->find_get("abc/def")->in_attr('var'));
eq("true",$tag->find_get("abc/def/ghi",2)->in_attr('ab'));

eq('selected',$tag->find_get("abc/def/ghi")->in_attr('selected'));
eq(null,$tag->find_get("abc/def/ghi",1)->in_attr('selected'));
eq(array(),$tag->find_get("abc/def")->find('xyz'));


