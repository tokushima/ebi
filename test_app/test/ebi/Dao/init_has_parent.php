<?php
\test\db\InitHasParent::create_table();
\test\db\ExtraInitHasParent::create_table();


$obj = new \test\db\InitHasParent();
$columns = $obj->columns();
eq(2,sizeof($columns));
foreach($columns as $column){
	eq(true,($column instanceof \ebi\Column));
}

try{
	$result = \test\db\ExtraInitHasParent::find_all();
}catch(Excepton $e){
	fail();
}
