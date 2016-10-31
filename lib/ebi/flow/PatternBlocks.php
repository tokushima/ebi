<?php
namespace ebi\flow;
/**
 * リクエストされたファイルをテンプレートとして利用するFlowパーツ
 * @author tokushima
 */
class PatternBlocks{
	use \ebi\FlowPlugin;
	
	/**
	 * リクエストされたファイルをテンプレートとして利用する
	 * @param string $filename
	 */
	public function select($filename){
		if(!empty($filename)){
			if($filename[0] === '/') $filename = substr($filename,1);
			$path = $this->map_arg('path');
			if(!empty($path) && substr($path,-1) !== '/') $path = $path.'/';
			$this->set_template_block($path.$filename);
		}
	}
}