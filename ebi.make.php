<?php
/**
 *  php -d phar.readonly=0 **.php
 */
$filename = substr(basename(__FILE__),0,strpos(basename(__FILE__),'.'));
$output = __DIR__.'/'.$filename.'.phar';
$basedir = __DIR__.'/src/ebi/';
$basedirlen = strlen($basedir);

if(is_file($output)){
	unlink($output);
}
try{
	$phar = new Phar($output,0,$filename.'.phar');
	
	foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
			$basedir,
			FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS
			),RecursiveIteratorIterator::SELF_FIRST
	) as $f){
		$phar[substr($f,$basedirlen)] = file_get_contents($f);
	}
	$stab = <<< 'STAB'
<?php
		Phar::mapPhar('%s.phar');
?>
STAB;
//	$phar->setStub(sprintf($stab,$filename));
	$phar->compressFiles(Phar::GZ);
	
	if(is_file($output)){
		print('Created '.$output.' ['.filesize($output).' byte]'.PHP_EOL);
	}else{
		print('Failed '.$output.PHP_EOL);
	}
}catch(UnexpectedValueException $e){
	print($e->getMessage().PHP_EOL.'usage: php -d phar.readonly=0 '.basename(__FILE__).PHP_EOL);
}catch (Exception $e){
	var_dump($e->getMessage());
	var_dump($e->getTraceAsString());
}
