<?php
namespace ebi;
/**
 * 値の検証クラス
 * @author tokushima
 *
 */
class Validator{
	/**
	 * @param string $t type
	 * @param mixed $v value
	 * @param mixed{} $p annotation values
	 * @throws \InvalidArgumentException
	 */
	public static function type($name,$v,$p=[]){
		if($v === null){
			return null;
		}
		$t = array_key_exists('type',$p) ? $p['type'] : 'mixed';
		
		try{
			switch($t){
				case null: 
					return $v;
				case 'string':
				case 'text':
					if(is_array($v)){
						throw new \ebi\exception\InvalidArgumentException();
					}
					$v = is_bool($v) ? (($v) ? 'true' : 'false') : ((string)$v);
					return ($t == 'text') ? $v : str_replace(["\r\n","\r","\n"],'',$v);
				default:
					if($v === ''){
						return null;
					}
					switch($t){
						case 'number':
							if(!is_numeric($v)){
								throw new \ebi\exception\InvalidArgumentException();
							}
							$dp = isset($p['decimal_places']) ? $p['decimal_places'] : null;
							return (float)(isset($dp) ? (floor($v * pow(10,$dp)) / pow(10,$dp)) : $v);
						case 'serial':
						case 'integer':
							if(!is_numeric($v) || (int)$v != $v){
								throw new \ebi\exception\InvalidArgumentException();
							}
							return (int)$v;
						case 'boolean':
							if(is_string($v)){
								$v = ($v === 'true' || $v === '1') ? true : (($v === 'false' || $v === '0') ? false : $v);
							}else if(is_int($v)){
								$v = ($v === 1) ? true : (($v === 0) ? false : $v);
							}
							if(!is_bool($v)){
								throw new \ebi\exception\InvalidArgumentException();
							}
							return (boolean)$v;
						case 'timestamp':
						case 'date':
							if(ctype_digit((string)$v) || (substr($v,0,1) == '-' && ctype_digit(substr($v,1)))){
								return (int)$v;
							}
							if(preg_match('/^0+$/',preg_replace('/[^\d]/','',$v))){
								return null;
							}
							$time = strtotime($v);
							
							if($time === false){
								throw new \ebi\exception\InvalidArgumentException();
							}
							return $time;
						case 'time':
							if(is_numeric($v)){
								return $v;
							}
							$d = array_reverse(preg_split("/[^\d\.]+/",$v));
							
							if($d[0] === ''){
								array_shift($d);
							}
							list($s,$m,$h) = [(isset($d[0]) ? (float)$d[0] : 0),(isset($d[1]) ? (float)$d[1] : 0),(isset($d[2]) ? (float)$d[2] : 0)];
							if(sizeof($d) > 3 || $m > 59 || $s > 59 || strpos($h,'.') !== false || strpos($m,'.') !== false){
								throw new \ebi\exception\InvalidArgumentException();
							}
							return ($h * 3600) + ($m*60) + ((int)$s) + ($s-((int)$s));
						case 'intdate':
							if(preg_match("/^\d\d\d\d\d+$/",$v)){
								$v = sprintf('%08d',$v);
								list($y,$m,$d) = [(int)substr($v,0,-4),(int)substr($v,-4,2),(int)substr($v,-2,2)];
							}else{
								$x = preg_split("/[^\d]+/",$v);
								if(sizeof($x) < 3){
									throw new \ebi\exception\InvalidArgumentException();
								}
								list($y,$m,$d) = [(int)$x[0],(int)$x[1],(int)$x[2]];
							}
							if($m < 1 || $m > 12 || $d < 1 || $d > 31 || (in_array($m,[4,6,9,11]) && $d > 30) || (in_array($m,[1,3,5,7,8,10,12]) && $d > 31)
									|| ($m == 2 && ($d > 29 || (!(($y % 4 == 0) && (($y % 100 != 0) || ($y % 400 == 0)) ) && $d > 28)))
							){
								throw new \ebi\exception\InvalidArgumentException();
							}
							return (int)sprintf('%d%02d%02d',$y,$m,$d);
						case 'email':
							$v = trim($v);
							if(!preg_match('/^[\w\''.preg_quote('./!#$%&*+-=?^_`{|}~','/').']+@(?:[A-Z0-9-]+\.)+[A-Z]{2,6}$/i',$v)
								|| strlen($v) > 255 || strpos($v,'..') !== false || strpos($v,'.@') !== false || $v[0] === '.'
							){
								throw new \ebi\exception\InvalidArgumentException();
							}
							return $v;
						case 'alnum':
							$a = $dp = isset($p['additional_chars']) ? $p['additional_chars'] : '';
							if(!ctype_alnum((empty($a) ? $v : str_replace(str_split($a,1),'',$v)))){
								throw new \ebi\exception\InvalidArgumentException();
							}
							return $v;
						case 'mixed':
							return $v;
						default:
							if(!($v instanceof $t)){
								throw new \ebi\exception\InvalidArgumentException();
							}
							return $v;
					}
			}
		}catch(\ebi\exception\InvalidArgumentException $e){
			throw new \ebi\exception\InvalidArgumentException($name.' must be an '.$t);
		}
	}
	
	/**
	 * 
	 * @param string $name 
	 * @param mixed $v
	 * @param mixed{} $anon
	 */
	public static function value($name,$v,$anon){
		$e_require = false;
		$get = function($an) use($anon){
			return isset($anon[$an]) ? $anon[$an] : null;
		};
		if($get('require') === true && ($v === '' || $v === null)){
			\ebi\Exceptions::add(new \ebi\exception\RequiredException($name.' required'),$name);
		}else if($v !== null){
			switch($get('type')){
				case 'number':
				case 'integer':
					if($get('min') !== null && (float)$get('min') > $v){
						\ebi\Exceptions::add(new \ebi\exception\LengthException($name.' less than minimum'),$name);
					}
					if($get('max') !== null && (float)$get('max') < $v){
						\ebi\Exceptions::add(new \ebi\exception\LengthException($name.' exceeds maximum'),$name);
					}
					break;
				case 'text':
				case 'string':
				case 'alnum':
					if($get('min') !== null && (int)$get('min') > mb_strlen($v)){
						\ebi\Exceptions::add(new \ebi\exception\LengthException($name.' less than minimum'),$name);
					}
					if($get('max') !== null && (int)$get('max') < mb_strlen($v)){
						\ebi\Exceptions::add(new \ebi\exception\LengthException($name.' exceeds maximum'),$name);
					}
					break;
			}
		}		
	}
}