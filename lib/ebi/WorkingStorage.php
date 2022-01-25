<?php
namespace ebi;

class WorkingStorage{
	static private $work;
	private $list = [];
	
	public function __destruct(){
		rsort($this->list);
		
		foreach($this->list as $f){
			$p = \ebi\Conf::work_path($f);
			
			if(is_file($p) || is_dir($p)){
				\ebi\Util::rm($p,true);
			}
		}
	}
	
	/**
	 * ワーキングファイルパスの取得
	 */
	public static function path(string $path=''): string{
		if(!isset(self::$work)){
			self::$work = new self();
		}
		$path = \ebi\Util::path_slash($path,false);
		self::$work->list[] = $path;
		
		return \ebi\Conf::work_path($path);
	}
	
	/**
	 * フォルダの作成
	 */
	public static function mkdir(string $path=''): string{
		$p = self::path($path);
		\ebi\Util::mkdir($p);
		
		return $p;
	}
	
	/**
	 * ワーキングファイルに書き出し
	 */
	public static function set(string $path, string $src=''): string{
		$p = self::path($path);
		\ebi\Util::file_write($p,$src);
		return $p;
	}
	/**
	 * ワーキングファイルから取得
	 */
	public static function get(string $path): string{
		return \ebi\Util::file_read(\ebi\Conf::work_path($path));
	}
	
	/**
	 * テンポラリファイルとして作成する
	 */
	public static function tmpfile(string $src='', string $postfix=''): string{
		for($i=0;$i<100;$i++){
			$uniq = str_replace('.','_',microtime(true).uniqid('',true)).$postfix;
			
			if(!file_exists(\ebi\Conf::work_path($uniq))){
				return self::set($uniq,$src);
			}
		}
		throw new \ebi\exception\RetryLimitOverException();
	}
	/**
	 * テンポラリディレクトリとして作成する
	 */
	public static function tmpdir(): string{
		for($i=0;$i<100;$i++){
			$uniq = str_replace('.','_',microtime(true).uniqid('',true));
			
			if(!file_exists(\ebi\Conf::work_path($uniq))){
				return self::mkdir($uniq);
			}
		}
		throw new \ebi\exception\RetryLimitOverException();
	}
}

