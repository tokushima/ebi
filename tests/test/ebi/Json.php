<?php
eq(10,\ebi\Json::decode('10'));
eq(['abc'=>10],\ebi\Json::decode('{"abc":10}'));

try{
	\ebi\Json::decode('{"abc"10}');
	fail();
}catch(\ebi\exception\InvalidArgumentException $e){
	
}

eq('{"abc":10}',\ebi\Json::encode(['abc'=>10]));


eq('{"def":"aaa","ghi":100,"jkl":null}',\ebi\Json::encode(new \test\Json()));


$obj = new \test\Json();
$obj->jkl(new \test\Json());
eq('{"def":"aaa","ghi":100,"jkl":{"def":"aaa","ghi":100,"jkl":null}}',\ebi\Json::encode($obj));


$json = new \ebi\Json(null);
eq([null], $json->find());
