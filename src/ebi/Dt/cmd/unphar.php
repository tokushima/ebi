<?php
/**
 * pharを展開する
 * @param string $f 展開したいpharファイル
 * @param string $o 出力先
 */
$f = realpath($f);

if($f === false){
	throw new \InvalidArgumentException($f.' not foundf');
}
if(empty($o)){
	$o = getcwd().'/'.basename($f,'.phar');
}

$out = \ebi\Archive::unphar($f,$o);

\cmdman\Std::println_info('Written '.$out);

