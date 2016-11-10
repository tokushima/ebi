<?php
namespace ebi\flow;
/**
 * ファイルの一覧を処理するFlowパーツ
 * @author tokushima
 * @conf string $document_root 一覧するディレクトリのベースパス
 */
class File{
	use \ebi\FlowPlugin;
	use \ebi\Plugin;
	/**
	 * 指定ディレクトリ以下すべてのファイルの一覧
	 */
	public function tree($path=null){
		$files = [];
		$pattern = $this->map_arg('pattern');
		if($pattern !== null) $path = vsprintf($pattern,func_get_args());
		$path = \ebi\Util::path_slash(
					\ebi\Util::path_absolute(
						$this->base(),
						\ebi\Util::path_slash($path,false,true)
					)
					,null
					,true
				);

		if(is_dir($path)){
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $e){
				$p = str_replace($path,'',str_replace('\\','/',$e->getPathname()));
				if($p[0] != '.') $files[] = $p;
			}
		}
		sort($files);
		return ['files'=>$files];
	}
	/**
	 * リクエストされたファイルを添付する
	 */
	public function attach($path=null){
		$pattern = $this->map_arg('pattern');
		if($pattern !== null) $path = vsprintf($pattern,func_get_args());
		/**
		 * attachする前に実行する
		 * @param string $path
		 */
		if($this->has_object_plugin('before_attach')){
			$this->call_object_plugin_funcs('before_attach',$path);
		}
		\ebi\HttpFile::attach(\ebi\Util::path_absolute($this->base(),$path));
	}
	private function base(){
		/**
		 * @param string $document_root 一覧を行うディレクトリ
		 */
		$base = \ebi\Util::path_slash(\ebi\Conf::get('document_root'),null,true);
		return $base.\ebi\Util::path_slash($this->map_arg('path'),null,true);
	}
}

