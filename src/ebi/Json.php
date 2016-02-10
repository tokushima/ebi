<?php
namespace ebi;
/**
 * JSON 文字列を操作する
 * @author tokushima
 *
 */
class Json{
	private $arr = [];
	
	/**
	 * JSONからオブジェクトを生成する
	 * @param string $json
	 * @return \ebi\Json
	 */
	public function __construct($json){
		$this->arr = self::decode($json);
	}
	/**
	 * パスから値を取得する
	 * @param string $name
	 * @return mixed
	 */
	public function find($name=null){
		if(empty($name)){
			return $this->arr;
		}
		$names = explode('/',$name);
		$arr = $this->arr;
		
		foreach($names as $key){
			if(is_array($arr) && array_key_exists($key,$arr)){
				$arr = $arr[$key];
			}else{
				return null;
			}
		}
		return $arr;
	}
	
	/**
	 * 値を JSON 形式にして返す
	 * @param mixed $val
	 * @return string
	 */
	public static function encode($val){
		$json = json_encode(self::encode_object($val));
		
		if(json_last_error() != JSON_ERROR_NONE){
			throw new \ebi\exception\InvalidArgumentException(json_last_error_msg());
		}
		return $json;
	}
	/**
	 * 値をJSON形式で整形された文字列を返す
	 * @param mixed $val
	 * @return string
	 * @throws \ebi\exception\InvalidArgumentException
	 */
	public static function format($val){
		$json = json_encode(self::encode_object($val),JSON_PRETTY_PRINT);
		
		if(json_last_error() != JSON_ERROR_NONE){
			throw new \ebi\exception\InvalidArgumentException(json_last_error_msg());
		}
		return $json;
	}
	
	private static function encode_object($val){
		if(is_object($val) || is_array($val)){
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
		if(is_null($json) || $json === ''){
			return null;
		}
		$val = json_decode($json,true);
		
		if(json_last_error() != JSON_ERROR_NONE){
			throw new \ebi\exception\InvalidArgumentException(json_last_error_msg());
		}
		return $val;
	}
}