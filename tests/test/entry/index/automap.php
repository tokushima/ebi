<?php
/**
 * automapがあるものだけが定義される
 * required paramerter のものだけが定義される
 */
$b = b();

$b->do_get(url('index::ABC/index'));
eq(200,$b->status());

$b->do_get(url('index::ABC/def'));
eq(200,$b->status());

$b->do_get(url('index::ABC/ghi',1));
eq(200,$b->status());


try{
	url('index::ABC/jkl',1);
}catch(\testman\NotFoundException $e){
}


$b->do_get(url('index::ABC/jkl',1,2));
eq(200,$b->status());


try{
	url('index::ABC/jkl',1,2,3);
}catch(\testman\NotFoundException $e){
}


try{
	url('index::ABC/abc');
}catch(\testman\NotFoundException $e){
}



