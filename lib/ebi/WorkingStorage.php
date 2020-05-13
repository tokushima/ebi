<?php
namespace ebi;
/**
 * ワーキングファイル
 * @author tokushima
 *
 */
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
	 * @param string $path
	 * @return string
	 */
	public static function path($path){
		if(!isset(self::$work)){
			self::$work = new self();
		}
		$path = \ebi\Util::path_slash($path,false);
		self::$work->list[] = $path;
		
		return \ebi\Conf::work_path($path);
	}
	
	/**
	 * フォルダの作成
	 * @param string $path
	 * @reutrn string
	 */
	public static function mkdir($path){
		$p = self::path($path);
		\ebi\Util::mkdir($p);
		
		return $p;
	}
	
	/**
	 * ワーキングファイルに書き出し
	 * @param string $path
	 * @param string $src
	 * @return string ファイルパス
	 */
	public static function set($path,$src=null){
		$p = self::path($path);
		\ebi\Util::file_write($p,$src);
		return $p;
	}
	/**
	 * ワーキングファイルから取得
	 * @param string $path
	 * @return string
	 */
	public static function get($path){
		return \ebi\Util::file_read(\ebi\Conf::work_path($path));
	}
	
	/**
	 * テンポラリファイルとして作成する
	 * @param string $src テンポラリファイルに書き込む文字列
	 * @param string $postfix テンポラリファイル名の接尾文字列
	 * @throws \ebi\exception\AccessDeniedException
	 * @return string ファイルパス
	 */
	public static function tmpfile($src='',$postfix=''){
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
	 * @throws \ebi\exception\AccessDeniedException
	 * @return string ファイルパス
	 */
	public static function tmpdir(){
		for($i=0;$i<100;$i++){
			$uniq = str_replace('.','_',microtime(true).uniqid('',true));
			
			if(!file_exists(\ebi\Conf::work_path($uniq))){
				return self::mkdir($uniq);
			}
		}
		throw new \ebi\exception\RetryLimitOverException();
	}
}

