<?php
/**
 * ライブラリをpharにする
 * @param string $src ライブラリのルートフォルダ @['require'=>true]
 */

$src = realpath($src);
if($src === false || is_file($src)){
	throw new \InvalidArgumentException($src.' not found');
}
$src = $src.'/';
$ns = '';
$mkdir = [];
$files = [];

$srclen = strlen($src);
foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
	$src,
	\FilesystemIterator::SKIP_DOTS|\FilesystemIterator::UNIX_PATHS
),\RecursiveIteratorIterator::SELF_FIRST) as $r){
	if($r->isFile()){
		if(empty($ns)){
			$ns = str_replace($src,'',dirname($r->getPathname()));
		}
		$path = substr($r,$srclen);
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

$output = $ns.'.phar';
if(is_file($output)){
	unlink($output);
}
\ebi\Util::mkdir(dirname($output));

try{
	$phar = new \Phar($output,0,basename($output));

	foreach($mkdir as $m){
		$phar->addEmptyDir('src/'.$m);
	}
	foreach($files as $k => $v){
		$phar->addFile($v,'src/'.$k);
	}
	
	$stabstr = sprintf("Phar::mapPhar('%s');",basename($output));
	$stabstr .= sprintf(<<< 'STAB'
		spl_autoload_register(function($c){
			$c = str_replace('\\','/',$c);
			
			if(substr($c,0,4) == '%s/' && is_file($f='phar://'.__FILE__.'/src/'.$c.'.php')){
				require_once($f);
			}
			return false;
		},true,false);
STAB
	,$ns);

	
	if(is_file('autoload.php')){
		$phar->addFile('autoload.php','autoload.php');
		
		$stabstr .= sprintf(PHP_EOL
			."require_once('phar://%s/autoload.php');"
			,basename($output)
		);
	}
	$stabstr .= sprintf(PHP_EOL.<<< 'STAB'
		$dir = getcwd().'/lib';
		if(is_dir($dir) && strpos(get_include_path(),$dir) === false){
			set_include_path($dir.PATH_SEPARATOR.get_include_path());
		}
STAB
	);
	
	$phar->setStub(sprintf(<<< 'STAB'
<?php
	%s
	__HALT_COMPILER();
?>
STAB
	,$stabstr));
	$phar->compressFiles(\Phar::GZ);

	if(is_file($output)){
		\cmdman\Std::println_info('Created '.$output.' ['.filesize($output).' byte]');
	}else{
		\cmdman\Std::println_danger('Failed '.$output);
	}
}catch(\UnexpectedValueException $e){
	\cmdman\Std::println_info($e->getMessage().PHP_EOL.'usage: php -d phar.readonly=0 cmdman.phar ebi.Dt::phar --src '.str_replace(getcwd().'/','',$d));
}catch (\Exception $e){
	\cmdman\Std::println_danger($e->getMessage());
	\cmdman\Std::println_warning($e->getTraceAsString());
}
