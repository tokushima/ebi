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
		}
		$value= print_r($result,true);
		$value = str_replace('=>'.PHP_EOL,': ',trim($value));
		$value = preg_replace('/\[\d+\]/','&nbsp;&nbsp;\\0',$value);
		return implode(PHP_EOL,array_slice(explode(PHP_EOL,$value),2,-1));
	}

	public function md2html($v){
		$md = new \ebi\Md();
		return str_replace('{$','@VALPREFIX@',$md->html($v));
	}
}
