<?php
/**
 * ライブラリをpharで固める
 * @param string $d 対象のルートフォルダ @['require'=>true]
 * @param string $o pahrファイル名 @['require'=>true]
 */

$d = realpath($d);
if($d === false){
	throw new \InvalidArgumentException($d.' not found');
}

if(strpos($o,'.phar') === false){
	$o = $o.'.phar';
}
\ebi\Util::mkdir(dirname($o));
$output = $o;



$basedir = $d;
$basedirlen = strlen($basedir);

if(is_file($output)){
	unlink($output);
}


try{
	$mkdir = [];
	$files = [];
	$phar = new \Phar($output,0,basename($output));

	foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
			$basedir,
			\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
	),\RecursiveIteratorIterator::SELF_FIRST
	) as $r){
		if($r->isFile()){
			$path = substr($r,$basedirlen);
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
			$files[$path] = $r->getPathname();
		}
	}
	ksort($mkdir);

	foreach($mkdir as $m){
		$phar->addEmptyDir($m);
	}
	foreach($files as $k => $v){
		$phar->addFile($v,$k);
	}

	$stab = <<< 'STAB'
<?php
	__HALT_COMPILER();
?>
STAB;
	
	$filename = basename($o,'.phar');
	$phar->setStub(sprintf($stab,$filename,$filename));
	$phar->compressFiles(\Phar::GZ);

	if(is_file($output)){
		\cmdman\Std::println_info('Created '.$output.' ['.filesize($output).' byte]');
	}else{
		\cmdman\Std::println_danger('Failed '.$output);
	}
}catch(\UnexpectedValueException $e){
	\cmdman\Std::println_info($e->getMessage().PHP_EOL.'usage: php -d phar.readonly=0 cmdman.phar ebi.Dt::phar -d '.str_replace(getcwd().'/','',$d).' -o '.$o);
}catch (Exception $e){
	\cmdman\Std::println_danger($e->getMessage());
	\cmdman\Std::println_warning($e->getTraceAsString());
}
