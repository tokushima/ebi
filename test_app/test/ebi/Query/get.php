<?php

eq("req=123",\ebi\Query::get("123","req"));
eq("req[0]=123",\ebi\Query::get(array(123),"req"));
eq("req[0]=123&req[1]=456&req[2]=789",\ebi\Query::get(array(123,456,789),"req"));
eq("",\ebi\Query::get(array(123,456,789)));
eq("abc=123&def=456&ghi=789",\ebi\Query::get(array("abc"=>123,"def"=>456,"ghi"=>789)));
eq("req[0]=123&req[1]=&req[2]=789",\ebi\Query::get(array(123,null,789),"req"));
eq("req[0]=123&req[2]=789",\ebi\Query::get(array(123,null,789),"req",false));
	
eq("req=123&req=789",\ebi\Query::get(array(123,null,789),"req",false,false));
eq("label=123&label=&label=789",\ebi\Query::get(array("label"=>array(123,null,789)),null,true,false));



$obj = new \test\query\Query();
$obj->id = 100;
$obj->value = "hogehoge";
eq("req[id]=100&req[value]=hogehoge&req[test]=TEST",\ebi\Query::get($obj,"req"));
eq("id=100&value=hogehoge&test=TEST",\ebi\Query::get($obj));

