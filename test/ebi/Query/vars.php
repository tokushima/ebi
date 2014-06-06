<?php
$array = array();
eq(array(array("abc",123),array("def",456)),\ebi\Query::expand_vars($array,array("abc"=>"123","def"=>456)));
eq(array(array("abc",123),array("def",456)),$array);
	
$array = array();
eq(array(array("hoge[abc]",123),array("hoge[def]",456)),\ebi\Query::expand_vars($array,array("abc"=>"123","def"=>456),'hoge'));
eq(array(array("hoge[abc]",123),array("hoge[def]",456)),$array);
	
$array = array();
eq(array(array("hoge[abc]",123),array("hoge[def][ABC]",123),array("hoge[def][DEF]",456)),\ebi\Query::expand_vars($array,array("abc"=>"123","def"=>array("ABC"=>123,"DEF"=>456)),'hoge'));
eq(array(array("hoge[abc]",123),array("hoge[def][ABC]",123),array("hoge[def][DEF]",456)),$array);



$obj = new \test\query\Vars();
$obj->id = 100;
$obj->value = "hogehoge";
	
$array = array();
eq(array(array('req[id]','100'),array('req[value]','hogehoge'),array('req[test]','TEST')),\ebi\Query::expand_vars($array,$obj,"req"));
eq(array(array('req[id]','100'),array('req[value]','hogehoge'),array('req[test]','TEST')),$array);

