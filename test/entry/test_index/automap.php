<?php
$b = b();

$b->do_get(url('test_index::ABC/index'));
eq(200,$b->status());

$b->do_get(url('test_index::ABC/def'));
eq(200,$b->status());

$b->do_get(url('test_index::ABC/ghi',1));
eq(200,$b->status());

$b->do_get(url('test_index::ABC/jkl',1,2));
eq(200,$b->status());


try{
	url('test_index::ABC/jkl',1,2,3);
}catch(\testman\NotFoundException $e){
}


try{
	url('test_index::ABC/abc');
}catch(\testman\NotFoundException $e){
}



