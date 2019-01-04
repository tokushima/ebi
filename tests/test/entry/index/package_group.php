<?php
$b = b();

$m = [];
$b->do_get('index::package_group_action_a/abc');

if(preg_match('/href="(.+?)"/', $b->body(),$m)){
	$b->do_get($m[1]);
	meq('font-size: 120px;',$b->body());
}else{
	fail();
}

$b->do_get('index::package_group_action_b/def');

if(preg_match('/href="(.+?)"/', $b->body(),$m)){
	$b->do_get($m[1]);
	meq('font-size: 120px;',$b->body());
}else{
	fail();
}
