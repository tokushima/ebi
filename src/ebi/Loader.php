<?php
namespace ebi;
/**
 * 
 * @author tokushima
 *
 */
class Loader{
	/**
	 * pharのパスを通す
	 * @param string $path
	 * @param string $namespace
	 * @throws \ebi\exception\InvalidArgumentException
	 */
	public static function phar($path,$namespace=null){
		$path = realpath($path);
		
		if($path === false){
			throw new \ebi\exception\InvalidArgumentException($path.' not found');
		}
		if(empty($namespace)){
			$namespace = str_replace('_','/',basename($path,'.phar'));
		}
		$namespace = str_replace("\\",'/',$namespace);
		
		if($namespace[0] == '/'){
			$namespace = substr($namespace,1);
		}		
		spl_autoload_register(function($c) use($path,$namespace){
			$c = str_replace('\\','/',$c);
			if(strpos($c,$namespace) === 0 && is_file($f='phar://'.$path.'/'.$c.'.php')){
				require_once($f);
			}
			return false;
		},true,false);
	}
}
