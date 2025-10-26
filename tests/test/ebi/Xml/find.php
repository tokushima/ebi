<?php

$x = \ebi\Xml::anonymous("<asd><abc><def><ghi>AAA</ghi><ghi>BBB</ghi><ghi>CCC</ghi></def></abc></asd>");
$i = 0;
foreach($x->find('abc') as $t){
	$i++;
	eq('<def><ghi>AAA</ghi><ghi>BBB</ghi><ghi>CCC</ghi></def>',$t->value());
}
eq(1,$i);

$x = \ebi\Xml::anonymous("<asd><abc><def><ghi>AAA</ghi><ghi>BBB</ghi><ghi>CCC</ghi></def></abc></asd>");
$i = 0;
foreach($x->find('abc/def/ghi') as $t){
	$i++;
}
eq(3,$i);

$x = \ebi\Xml::anonymous("<asd><abc><def><ghi>ABC</ghi><ghi>XYZ</ghi></def></abc></asd>");
eq('ABC',$x->find_get('abc/def/ghi')->value());

$x = \ebi\Xml::anonymous("<asd><abc><def><ghi>ABC</ghi><ghi>XYZ</ghi></def></abc></asd>");
eq('XYZ',$x->find_get('abc/def/ghi',1)->value());


$x = \ebi\Xml::anonymous("<asd><abc><def><ghi>ABC</ghi><ghi>XYZ</ghi></def></abc></asd>");
eq('XYZ',$x->find_get('abc/def/ghi',1)->value());

$x = \ebi\Xml::anonymous("<asd><abc><def><jkl>aaa</jkl><ghi>ABC</ghi><jkl>bbb</jkl><ghi>XYZ</ghi></def></abc></asd>");
eq('XYZ',$x->find_get('abc/def/ghi',1)->value());

$x = \ebi\Xml::anonymous("<asd><abc><def><jkl>aaa</jkl><ghi>ABC</ghi><jkl>bbb</jkl><ghi>XYZ</ghi></def></abc></asd>");
eq('bbb',$x->find_get('abc/def/jkl',1)->value());


$x = \ebi\Xml::anonymous("<asd><abc><def><jkl>aaa</jkl><ghi>ABC</ghi><jkl>bbb</jkl><ghi>XYZ</ghi></def></abc></asd>");
eq('bbb',$x->find_get('abc/def/ghi|jkl',2)->value());


$x = \ebi\Xml::anonymous("<xml><a><x>A</x></a><c><x>C</x></c><b><x>B</x></b></xml>");
eq('C',$x->find_get('b|c/x')->value());



$x = \ebi\Xml::anonymous("<xml> <a><b><e>NO1</e></b></a> <a><b><c>A</c></b></a> <a><b><c>B</c></b></a>  <a><b><c>C</c></b></a> </xml>");
eq('A',$x->find_get('a/b/c')->value());
$i = 0;
foreach($x->find('a/b/c') as $f){
	$i++;
}
eq(1,$i);


$x = \ebi\Xml::anonymous("<xml> <a><b><e>NO1</e></b></a> <a><b><c>A</c></b></a> <a><b><c>B</c></b></a>  <a><b><c>C</c></b></a> </xml>");
try{
	$x->find_get('a/b/c',1)->value();
	fail();
}catch(\ebi\exception\NotFoundException $e){
}

$x = \ebi\Xml::anonymous("<xml> <a><b><e>NO1</e><c>A</c><c>B</c><c>C</c></b></a> </xml>");
eq('C',$x->find_get('a/b/c',2)->value());

$i = 0;
foreach($x->find('a/b/c') as $f){
	$i++;
}
eq(3,$i);


$x = \ebi\Xml::anonymous("<a><b><data>a</data><data>b</data><data>c</data></b></a>");
eq(['b'=>['a','b','c']],$x->find_get('a')->children());

$j = new \ebi\Json('{"a":{"b":["a","b","c"]}}');
eq(['b'=>['a','b','c']],$j->find('a'));


$x = \ebi\Xml::anonymous("<xml> <a><b><Cc>a</Cc><Cc>b</Cc><Cc>c</Cc></b><b><Cc>A</Cc><Cc>B</Cc><Cc>C</Cc></b></a></xml>");
eq(['b'=>[['a','b','c'],['A','B','C']]],$x->find_get('a')->children());


$j = new \ebi\Json('{"a":{"b":[["a","b","c"],["A","B","C"]]}}');
eq(['b'=>[['a','b','c'],['A','B','C']]],$j->find('a'));



$x = \ebi\Xml::anonymous('<result><new_list><DataModel><id>1</id><no>100</no></DataModel><DataModel><id>2</id><no>200</no></DataModel></new_list></result>');
eq([['id'=>1,'no'=>100],['id'=>2,'no'=>200]],$x->find_get('result/new_list')->children());


$x = \ebi\Xml::anonymous('<result><new_list><DataModel><id>3</id><no>300</no></DataModel></new_list></result>');
eq([['id'=>3,'no'=>300]],$x->find_get('result/new_list')->children());




