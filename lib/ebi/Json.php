<?php
namespace ebi;

class Json{
	private array $arr = [];
	
	public function __construct(?string $json){
		$this->arr = self::decode($json);
	}

	/**
	 * パスから値を取得する
	 * @return mixed
	 */
	public function find(?string $name=null){
		if(empty($name)){
			return $this->arr;
		}
		$names = explode('/',$name);
		$arr = $this->arr;
		
		foreach($names as $key){
			if(is_array($arr) && array_key_exists($key,$arr)){
				$arr = $arr[$key];
			}else{
				throw new \ebi\exception\NotFoundException();
			}
		}
		return $arr;
	}
	
	/**
	 * 値を JSON 形式にして返す
	 * @param mixed $val
	 */
	public static function encode($val, bool $pretty_print=false, bool $unescaped_unicode=false): string{
		$opt = 0;
		if($pretty_print){
			$opt = $opt | JSON_PRETTY_PRINT;
		}
		if($unescaped_unicode){
			$opt = $opt | JSON_UNESCAPED_UNICODE;
		}
		$json = json_encode(self::encode_object($val),$opt);
		
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
	 * @return mixed
	 */
	public static function decode(?string $json){
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