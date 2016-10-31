<?php
$flow = new \ebi\Flow();
$map = $flow->get_map(dirname(dirname(dirname(__DIR__))).'/index.php');
$patterns = $map['patterns'];

if(eq(true,isset($patterns['template_abc']))){
	eq('template_abc',$patterns['template_abc']['name']);
}
if(eq(true,isset($patterns['ABC/jkl/(.+)/(.+)']))){
	eq('ABC/jkl',$patterns['ABC/jkl/(.+)/(.+)']['name']);
}
if(eq(true,isset($patterns['template_abc/def']))){
	eq('template_def',$patterns['template_abc/def']['name']);
}

