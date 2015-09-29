<?php
// TODO あとでやる
/**
 *  php -d phar.readonly=0 **.php
*/
return;

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
	$mkdir = [];
	$files = [];
	$phar = new Phar($output,0,$filename.'.phar');

	foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
			$basedir,
			FilesystemIterator::SKIP_DOTS|FilesystemIterator::UNIX_PATHS
	),RecursiveIteratorIterator::SELF_FIRST
	) as $f){
		if($f->isFile()){
			$path = substr($f,$basedirlen);
			$dir = dirname($path);

			if($dir != '.'){
				$d = explode('/',$dir);
				while(!empty($d)){
					$dp = implode('/',$d);

					if(isset($mkdir[$dp])){
						break;
					}
					$mkdir[$dp] = $dp;
					array_shift($d);
				}
			}
			$files[$path] = $f->getPathname();
		}
	}
	ksort($mkdir);

	foreach($mkdir as $d){
		$phar->addEmptyDir($d);
	}
	foreach($files as $k => $v){
		$phar->addFile($v,$k);
	}

	$stab = <<< 'STAB'
<?php
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
