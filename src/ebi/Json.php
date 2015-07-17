<?php
namespace ebi;
/**
 * JSON 文字列を操作する
 * @author tokushima
 *
 */
class Json{
	/**
	 * 値を JSON 形式にして返す
	 * @param mixed $val
	 * @return multitype:Ambigous <string, multitype:string > |string
	 */
	public static function encode($val){
		$json = json_encode(self::encode_object($val));
		
		if(json_last_error() != JSON_ERROR_NONE){
			throw new \ebi\exception\InvalidArgumentException(json_last_error_msg());
		}		
		return $json;
	}
	private static function encode_object($val){
		if(is_object($val)){
			$rtn = [];
			
			foreach($val as $k => $v){
				$rtn[$k] = self::encode_object($v);
			}
			return $rtn;
		}
		return $val;
	}
	/**
	 * JSON 文字列をデコードする
	 * @param string $json
	 * @return mixed
	 */
	public static function decode($json){
		$val = json_decode($json,true);
		
		if(json_last_error() != JSON_ERROR_NONE){
			throw new \ebi\exception\InvalidArgumentException(json_last_error_msg());
		}
		return $val;
	}
}