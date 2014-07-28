<?php
/**
 *  php -d phar.readonly=0 **.php
 */
$filename = substr(basename(__FILE__),0,strpos(basename(__FILE__),'.'));

if(isset($argv[1])){
	$output = $argv[1];
}else{
	$output = __DIR__.'/'.$filename.'.phar';
}
$basedir = __DIR__.'/src/';
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
	$phar['autoload.php'] = file_get_contents(__DIR__.'/autoload.php');
	
	$stab = <<< 'STAB'
<?php
	spl_autoload_register(function($c){
		$c = str_replace('\\','/',$c);
		if(substr($c,0,4) == 'ebi/' && is_file($f='phar://'.__FILE__.'/'.$c.'.php')){
			require_once($f);
		}
		return false;
	},true,false);

	Phar::mapPhar('%s.phar');
	require_once('phar://%s.phar/autoload.php');
	
	__HALT_COMPILER();
?>
STAB;
	$phar->setStub(sprintf($stab,$filename,$filename));
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
