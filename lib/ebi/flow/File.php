<?php
namespace ebi\flow;
/**
 * ファイルを返す
 * @author tokushima
 *
 */
class File{
	private $root;

	/**
	 * 
	 * @param string $root ファイルを探すルートパス
	 */
	public function __construct($root=null){
		if(empty($root)){
			$root = \ebi\Conf::resource_path('files');
		}
		$this->root = $root;
	}

	/**
	 * ファイルを返す
	 * @param string $path
	 */
	public function inline($path){
		$path = \ebi\Util::path_absolute($this->root,$path);
		
		if(!is_file($path)){
			\ebi\HttpHeader::send_status(404);
			exit;
		}
		\ebi\HttpFile::inline($path);
	}
}



