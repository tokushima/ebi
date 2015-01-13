<?php
$p = "<abc><def>111</def></abc>";
$x = \ebi\Xml::extract($p,'abc');
eq("abc",$x->name());

$p = "<abc><def>111</def></abc>";
$x = \ebi\Xml::extract($p,"def");
eq("def",$x->name());
eq(111,$x->value());

try{
	$p = "aaaa";
	\ebi\Xml::extract($p,'abc');
	fail();
}catch(\ebi\exception\NotFoundException $e){
}

try{
	$p = "<abc>sss</abc>";
	\ebi\Xml::extract($p,"def");
	fail();
}catch(\ebi\exception\NotFoundException $e){
}

$p = "<abc>sss</a>";
$x = \ebi\Xml::extract($p,'abc');
eq("<abc />",$x->get());

$p = "<abc>0</abc>";
$x = \ebi\Xml::extract($p,'abc');
eq("abc",$x->name());
eq("0",$x->value());


