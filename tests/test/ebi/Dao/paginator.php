<?php
\test\db\Paginator::create_table();
\test\db\Paginator::find_delete();

for($i=1;$i<=98;$i++){
	$obj = new \test\db\Paginator();
	$obj->order($i);
	$obj->save();
}

$i = 0;
$paginator = new \ebi\Paginator(20,1);
foreach(test\db\Paginator::find($paginator,\ebi\Q::order('id')) as $o){
	$i++;
}
eq(20,$i);


$i = 0;
$paginator = new \ebi\Paginator(20,2);
foreach(test\db\Paginator::find($paginator,\ebi\Q::order('id')) as $o){
	$i++;
}
eq(20,$i);


$i = 0;
$paginator = new \ebi\Paginator(20,3);
foreach(test\db\Paginator::find($paginator,\ebi\Q::order('id')) as $o){
	$i++;
}
eq(20,$i);


$i = 0;
$paginator = new \ebi\Paginator(20,4);
foreach(test\db\Paginator::find($paginator,\ebi\Q::order('id')) as $o){
	$i++;
}
eq(20,$i);


$i = 0;
$paginator = new \ebi\Paginator(20,5);
foreach(test\db\Paginator::find($paginator,\ebi\Q::order('id')) as $o){
	$i++;
}
eq(18,$i);


$i = 0;
$paginator = new \ebi\Paginator(20,6);
foreach(test\db\Paginator::find($paginator,\ebi\Q::order('id')) as $o){
	$i++;
}
eq(0,$i);



$i = 0;
$req = new \ebi\Request();
$req->vars('paginate_by',30); // 30を指定してもmax25なので25まで
$paginator = \ebi\Paginator::request($req,20,25);
foreach(test\db\Paginator::find($paginator,\ebi\Q::order('id')) as $o){
	$i++;
}
eq(25,$i);

