<?php
$start = microtime(true);
$max = 1000;

$db = new \ebi\Db(['type'=>'ebi.SqliteConnector','host'=>':memory:','encode'=>'utf8']);

$table_name = 'test_'.uniqid();
$db->query(sprintf('create table `%s`(id INTEGER PRIMARY KEY AUTOINCREMENT,value TEXT)',$table_name));

for($i=1;$i<$max;$i++){
	$db->query(sprintf('insert into `%s`(value) values(?)',$table_name),$i);
}
$db->query(sprintf('select id,value from `%s`',$table_name));

$i = 1;
foreach($db as $data){
	if($i != $data['value']) notice($i.': '.$data['value']);
	$i++;
}
eq($max,$i);

$time = microtime(true) - $start;
if($time > 1) notice($time);


$db->query(sprintf('select id,value from `%s` where id=?',$table_name),1);
foreach($db as $data){
	eq(1,$data['id']);
}

$db->re(2);
foreach($db as $data){
	eq(2,$data['id']);
}