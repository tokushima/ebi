<?php
namespace ebi\Dt;
/**
 * DT用のヘルパー
 * @author tokushima
 *
 */
class Helper{
	/**
	 * print_r
	 * @param mixed $obj
	 */
	public function dump($obj){
		$result = [];
		
		foreach($obj as $k => $v){
			if(isset($obj[$k])){
				if(!is_array($obj[$k]) || !empty($obj[$k])){
					$result[$k] = $v;
				}
			}
			if(is_bool($obj[$k])){
				$result[$k] = ($result[$k]) ? 'true' : 'false';
			}
		}
		if(isset($result['class']) && is_string($result['class'])){
			$result['class'] = \ebi\Util::get_class_name($result['class']);
		}
		return json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	}
}
