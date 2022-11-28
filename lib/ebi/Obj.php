<?php
namespace ebi;

class Obj implements \IteratorAggregate{
	private static array $_m = [];
	protected string $_;

	/**
	 * プロパティのアノテーションを取得する
	 * @param $p プロパティ名
	 * @param $n アノテーション名
	 * @param mixed $d デフォルト値
	 * @param $f 値をデフォルト値で上書きするか
	 * @return mixed
	 */
	public function prop_anon(string $p, ?string $n=null, $d=null, bool $f=false){
		if($f){
			self::$_m[get_class($this)][$p][$n] = $d;
		}
		if($n === null){
			return (isset(self::$_m[get_class($this)][$p])) ? self::$_m[get_class($this)][$p] : [];
		}
		return (isset(self::$_m[get_class($this)][$p][$n])) ? self::$_m[get_class($this)][$p][$n] : $d;
	}
	/**
	 * プロパティの一覧を取得する、アノテーション hash=false のものは含まない
	 * @see \IteratorAggregate::getIterator()
	 */
	public function getIterator(): \Traversable{
		$r = [];
		foreach(array_keys($this->props()) as $n){
			if($this->prop_anon($n,'get') !== false && $this->prop_anon($n,'hash') !== false){
				switch($this->prop_anon($n,'type')){
					case 'bool':
					case 'boolean':
						$r[$n] = $this->{$n}();
						break;
					default:
						$r[$n] = $this->{'fm_'.$n}();
				}
			}
		}
		return new \ArrayIterator($r);
	}
	public function __construct(){
		$c = get_class($this);
		if(!isset(self::$_m[$c])){
			self::$_m[$c] = \ebi\Annotation::get_class($c,'var',null,__CLASS__);
		}
	}
	public function __call($n, $args){
		if($n[0] != '_'){
			$m = [];
			[$c, $p] = (in_array($n,array_keys(get_object_vars($this)))) ? 
				[(empty($args) ? 'get' : 'set'),$n] : 
				(preg_match("/^([a-z]+)_([a-zA-Z].*)$/",$n,$m) ? 
					[$m[1],$m[2]] : 
					[null,null]
				);
			
			if(method_exists($this,$am=('___'.$c.'___'))){
				$this->_ = $p;
				return call_user_func_array([$this,(method_exists($this,$m=('__'.$c.'_'.$p.'__')) ? $m : $am)],$args);
			}
		}
		throw new \ebi\exception\BadMethodCallException(get_class($this).'::'.$n.' method not found');
	}
	/**
	 * アクセス可能なプロパティを取得する
	 */
	public function props(bool $format=false): array{
		$r = [];
		foreach(array_keys(get_object_vars($this)) as $n){
			if($n[0] != '_'){
				$r[$n] = ($format) ? $this->{'fm_'.$n}() : $this->{$n}();
			}
		}
		return $r;
	}
	private function ___get___(){
		if($this->prop_anon($this->_,'get') === false){
			throw new \ebi\exception\InvalidArgumentException('not permitted');
		}
		if($this->prop_anon($this->_,'attr') !== null){
			return (is_array($this->{$this->_})) ? $this->{$this->_} : (is_null($this->{$this->_}) ? [] : [$this->{$this->_}]);
		}
		return $this->{$this->_};
	}
	private function ___set___($v){
		if($this->prop_anon($this->_,'set') === false){
			throw new \ebi\exception\InvalidArgumentException('not permitted');
		}
		$anon = $this->prop_anon($this->_);
		switch($this->prop_anon($this->_,'attr')){
			case 'a':
				$v = (func_num_args() > 1) ? func_get_args() : (is_array($v) ? $v : [$v]);
				foreach($v as $a){
					$this->{$this->_}[] = \ebi\Validator::type($this->_,$a,$anon);
				}
				break;
			case 'h':
				$v = (func_num_args() === 2) ? [func_get_arg(0)=>func_get_arg(1)] : (is_array($v) ? $v : [(string)$v=>$v]);
				foreach($v as $k => $a){
					$this->{$this->_}[$k] = \ebi\Validator::type($this->_,$a,$anon);
				}
				break;
			default:
				$this->{$this->_} = \ebi\Validator::type($this->_,$v,$anon);
		}
		return $this;
	}
	private function ___rm___(){
		if($this->prop_anon($this->_,'set') === false){
			throw new \ebi\exception\InvalidArgumentException('not permitted');
		}
		if($this->prop_anon($this->_,'attr') === null){
			$this->{$this->_} = null;
		}else{
			if(func_num_args() == 0){
				$this->{$this->_} = [];
			}else{
				foreach(func_get_args() as $k) unset($this->{$this->_}[$k]);
			}
		}
	}
	private function ___fm___($f=null,$d=null){
		$p = $this->_;
		$v = (method_exists($this,$m=('__get_'.$p.'__'))) ? call_user_func([$this,$m]) : $this->___get___();
		switch($this->prop_anon($p,'type')){
			case 'datetime':
			case 'timestamp':
				return ($v === null) ? null : (date((empty($f) ? \ebi\Conf::timestamp_format() : $f),(int)$v));
			case 'date':
				return ($v === null) ? null : (date((empty($f) ? \ebi\Conf::date_format() : $f),(int)$v));
			case 'time':
				if($v === null){
					return 0;
				}
				$h = (int)floor($v / 3600);
				$i = (int)floor(($v - ($h * 3600)) / 60);
				$s = (int)floor($v - ($h * 3600) - ($i * 60));
				$m = (int)str_replace(' ','0',rtrim(str_replace('0',' ',(substr(($v - ($h * 3600) - ($i * 60) - $s),2,12)))));
				return (($h === 0) ? '' : $h.':').(sprintf('%02d:%02d',$i,$s)).(($m === 0) ? '' : '.'.$m);
			case 'intdate':
				if($v === null){
					return null;
				}
				return str_replace(['Y','m','d'],[substr($v,0,-4),substr($v,-4,2),substr($v,-2,2)],(empty($f) ? \ebi\Conf::date_format() : $f));
			case 'bool':
			case 'boolean':
				return ($v) ? (isset($d) ? $d : 'true') : (empty($f) ? 'false' : $f);
		}
		return $v;
	}
	private function ___ar___($i=null,$j=null){
		$v = $this->___get___();
		$a = is_array($v) ? $v : (($v === null) ? [] : [$v]);
		if(isset($i)){
			$c = 0;
			$l = ((isset($j) ? $j : sizeof($a)) + $i);
			$r = [];
			
			foreach($a as $k => $p){
				if($i <= $c && $l > $c) $r[$k] = $p;
				$c++;
			}
			return $r;
		}
		return $a;
	}
	private function ___in___($k=null,$d=null){
		$v = $this->___get___();
		return (isset($k)) ? ((is_array($v) && isset($v[$k]) && $v[$k] !== null) ? $v[$k] : $d) : $d;
	}
	private function ___is___($k=null){
		$v = $this->___get___();
		if($this->prop_anon($this->_,'attr') !== null){
			if($k === null) return !empty($v);
			$v = isset($v[$k]) ? $v[$k] : null;
		}
		switch($this->prop_anon($this->_,'type')){
			case 'string':
			case 'text': return (isset($v) && $v !== '');
			case 'bool':
			case 'boolean':
				return (bool)$v;
		}
		return isset($v);
	}
}
