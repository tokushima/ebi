<?php
/**
 * automapがあるものだけが定義される
 * required paramerter のものだけが定義される
 */
$b = b();

$b->do_get('index::ABC/index');
eq(200,$b->status());

$b->do_get('index::ABC/def');
eq(200,$b->status());

$b->do_get(['index::ABC/ghi',1]);
eq(200,$b->status());


try{
	\testman\Util::url('index::ABC/jkl',1);
}catch(\testman\NotFoundException $e){
}


$b->do_get(['index::ABC/jkl',1,2]);
eq(200,$b->status());


try{
	\testman\Util::url('index::ABC/jkl',1,2,3);
}catch(\testman\NotFoundException $e){
}


try{
	\testman\Util::url('index::ABC/abc');
}catch(\testman\NotFoundException $e){
}



