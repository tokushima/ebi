<?php
namespace ebi;
/**
 * Class Loader
 * @author tokushima
 *
 */
class Loader{
	static private $read = [];
	/**
	 * pharのパスを通す
	 * @param string $path
	 * @param string $namespace
	 */
	public static function phar($path,$namespace=null){
		/**
		 * @param string $arg1 pahrが格納されているディレクトリ
		 */
		$base = \ebi\Conf::get('path',getcwd());
		$path = realpath(\ebi\Util::path_absolute($base,$path));
		
		if(isset(self::$read[$path])){
			return true;
		}		
		if($path === false){
			throw new \ebi\exception\InvalidArgumentException($path.' not found');
		}
		if(!empty($namespace)){
			$namespace = str_replace("\\",'/',$namespace);
			
			if($namespace[0] == '/'){
				$namespace = substr($namespace,1);
			}
		}
		$package_dir = 'phar://'.(is_dir('phar://'.$path.'/src/') ? $path.'/src' : $path);
		
		spl_autoload_register(function($c) use($package_dir,$namespace){
			$c = str_replace(array('\\','_'),'/',$c);
			
			if((empty($namespace) || strpos($c,$namespace) === 0) && is_file($f=$package_dir.'/'.$c.'.php')){
				require_once($f);
			}
			return false;
		},true,false);
		
		self::$read[$path] = true;
		return true;
	}
}
