<?php
/**
 * ライブラリをpharにする
 * @param string $d ライブラリのルートフォルダ @['require'=>true]
 * @param string $o pahrファイル名 @['require'=>true]
 * @param string $autoload ロード時に読み込むautoloadファイル
 * @param string $ns autoloadさせるnamespace
 * @param string $include_path include_pathに追加する実行時パス
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
	$stabstr = '';
	
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
	if(!empty($ns)){
		if($ns[0] == '\\'){
			$ns = substr($ns,1);
		}
		if(substr($ns,-1) == '\\'){
			$ns = substr($ns,0,-1);
		}
		$ns = str_replace('\\','/',$ns);
		
		$stabstr .= sprintf(<<< 'STAB'
	spl_autoload_register(function($c){
		$c = str_replace('\\','/',$c);
		if(substr($c,0,4) == '%s/' && is_file($f='phar://'.__FILE__.'/'.$c.'.php')){
			require_once($f);
		}
		return false;
	},true,false);
STAB
		,$ns);
	}
	if(!empty($autoload)){
		$phar->addFile($autoload,'autoload.php');
		
		$stabstr .= sprintf(PHP_EOL
			."require_once('phar://%s/autoload.php');"
			,basename($output)
		);
	}
	if(!empty($include_path)){
		if($include_path[0] != '/'){
			$include_path = '/'.$include_path;
		}
		$stabstr .= sprintf(PHP_EOL.<<< 'STAB'
	$dir = getcwd().'%s';
	if(is_dir($dir) && strpos(get_include_path(),$dir) === false){
		set_include_path($dir.PATH_SEPARATOR.get_include_path());
	}
STAB
			,$include_path);
	}
	
	$stab = <<< 'STAB'
<?php
	Phar::mapPhar('%s');
	%s
	__HALT_COMPILER();
?>
STAB;
	
	$phar->setStub(sprintf($stab,basename($output),$stabstr));
	$phar->compressFiles(\Phar::GZ);

	if(is_file($output)){
		\cmdman\Std::println_info('Created '.$output.' ['.filesize($output).' byte]');
	}else{
		\cmdman\Std::println_danger('Failed '.$output);
	}
}catch(\UnexpectedValueException $e){
	\cmdman\Std::println_info($e->getMessage().PHP_EOL.'usage: php -d phar.readonly=0 cmdman.phar ebi.Dt::phar -d '.str_replace(getcwd().'/','',$d).' -o '.$o);
}catch (\Exception $e){
	\cmdman\Std::println_danger($e->getMessage());
	\cmdman\Std::println_warning($e->getTraceAsString());
}
