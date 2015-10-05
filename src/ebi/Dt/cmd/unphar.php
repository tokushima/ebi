<?php
/**
 * pharを展開する
 * @param string $f 展開したいpharファイル @['require'=>true]
 * @param string $o 出力先
 */

$f = realpath($f);

if($f === false){
	throw new \InvalidArgumentException($f.' not foundf');
}
if(!empty($o) && substr($o,-1) == '/'){
	$o = substr($o,1);
	
	\ebi\Util::mkdir($o);
	$o = realpath($o);
}

$it = new \RecursiveIteratorIterator(new \Phar($f),\RecursiveIteratorIterator::SELF_FIRST);

foreach($it as $i){
	if($i->isFile()){
		$file = str_replace('phar://'.$f,'',$i->getPathname());	
		
		if(!empty($o)){
			\ebi\Util::file_write($o.$file,file_get_contents($i->getPathname()));
			\cmdman\Std::println_info('Written '.$o.$file);
		}else{
			\cmdman\Std::println(' '.$file);
		}
	}
}

