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
		foreach($this->list as $f){
			$p = \ebi\Conf::work_path($f);
			
			if(is_file($p) || is_dir($p)){
				\ebi\Util::rm($p);
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
	 */
	public static function mkdir($path){
		\ebi\Util::mkdir(self::path($path));
	}
	
	/**
	 * ワーキングファイルに書き出し
	 * @param string $path
	 * @param string $src
	 */
	public static function set($path,$src=null){
		\ebi\Util::file_write(self::path($path),$src);
	}
	/**
	 * ワーキングファイルから取得
	 * @param string $path
	 * @return string
	 */
	public static function get($path){
		return \ebi\Util::file_read(\ebi\Conf::work_path($path));
	}	
}

