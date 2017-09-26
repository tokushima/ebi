<?php
\test\db\ManyColumn::create_table();
\test\db\ManyColumn::find_delete();


for($a=0;$a<100;$a++){
	$obj = new \test\db\ManyColumn();
	for($i=1;$i<=15;$i++){
		$obj->{'v'.$i}(rand(1,100));
	}
	$obj->save();
}


