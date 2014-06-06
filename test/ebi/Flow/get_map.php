<?php
$flow = new \ebi\Flow();
$maps = $flow->get_map(dirname(dirname(dirname(__DIR__))).'/test_index.php');

if(eq(true,isset($maps['template_abc']))){
	eq('template_abc',$maps['template_abc']['name']);
}
if(eq(true,isset($maps['ABC/jkl/(.+)/(.+)']))){
	eq('ABC/jkl',$maps['ABC/jkl/(.+)/(.+)']['name']);
}
if(eq(true,isset($maps['template_abc/def']))){
	eq('template_def',$maps['template_abc/def']['name']);
}

