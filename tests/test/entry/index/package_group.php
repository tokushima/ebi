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

$b->do_get('index::package_group_action_a/def');
meq('DEF',$b->body());


$b->do_get('index::package_group_action_a/ghi');
meq('ERROR',$b->body());


$b->do_get('index::package_group_action_a/jkl');
meq('JKL',$b->body());
meq('BASE',$b->body());
mneq('base',$b->body());

