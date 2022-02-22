<?php
namespace ebi\flow;

class File{
	protected $root;

	public function __construct(){
		/**
		 * 
		 * @param string $root ルートパス
		 */
		$this->root = \ebi\Conf::get('root',\ebi\Conf::resource_path('files'));
	}

	/**
	 * ダウンロード
	 */
	public function download(string $path): void{
		$path = \ebi\Util::path_slash($this->root,null,true).\ebi\Util::path_slash($path,false);
		
		if(!is_file($path)){
			\ebi\HttpHeader::send_status(404);
			exit;
		}
		\ebi\HttpFile::inline($path);
	}
}



