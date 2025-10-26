<?php
/**
 * built-in web serverで利用するテスト用のルーター
 * 
 * if (!-f $request_filename) {
 * 	rewrite ^(.+?)(/.*)$ $1.php$2?$query_string last;	
 * break;
 * }
 */
$uri = $_SERVER['REQUEST_URI'];
$exp = explode('/', substr($uri,1), 2);
$entry = $exp[0];
$pathinfo = $exp[1] ?? '';

$entry_file = __DIR__.'/'.$entry.'.php';
if(is_file($entry_file)){
	$_SERVER['PATH_INFO'] = '/'.$pathinfo;
	include($entry_file);
}else{
	header("HTTP/1.1 404 Not Found");
	print("404 Not Found");
}


