<?php
namespace ebi;
/**
 * 基底クラス
 * @author tokushima
 */
class Object implements \IteratorAggregate{
	private static $_m = array();
	protected $_;

	/**
	 * プロパティのアノテーションを取得する
	 * @param string $p プロパティ名
	 * @param string $n アノテーション名
	 * @param mixed $d デフォルト値
	 * @parama boolean $f 値をデフォルト値で上書きするか
	 * @return mixed
	 */
	public function prop_anon($p,$n,$d=null,$f=false){
		if($f) self::$_m[get_class($this)][$p][$n] = $d;
		$v = (isset(self::$_m[get_class($this)][$p][$n])) ? self::$_m[get_class($this)][$p][$n] : $d;
		return $v;
	}
	/**
	 * プロパティの一覧を取得する、アノテーション hash=false のものは含まない
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator(){
		$r = array();
		foreach(array_keys($this->props()) as $n){
			if($this->prop_anon($n,'get') !== false && $this->prop_anon($n,'hash') !== false){
				switch($this->prop_anon($n,'type')){
					case 'boolean': $r[$n] = $this->{$n}(); break;
					default: $r[$n] = $this->{'fm_'.$n}();
				}
			}
		}
		return new \ArrayIterator($r);
	}
	public function __construct(){
		$c = get_class($this);
		if(!isset(self::$_m[$c])){
			self::$_m[$c] = \ebi\Annotation::decode($c,'var',__CLASS__);
		}
		if(method_exists($this,'__init__')){
			$args = func_get_args();
			call_user_func_array(array($this,'__init__'),$args);
		}
	}
	public function __call($n,$args){
		if($n[0] != '_'){
			list($c,$p) = (in_array($n,array_keys(get_object_vars($this)))) ? array((empty($args) ? 'get' : 'set'),$n) : (preg_match("/^([a-z]+)_([a-zA-Z].*)$/",$n,$m) ? array($m[1],$m[2]) : array(null,null));
			if(method_exists($this,$am=('___'.$c.'___'))){
				$this->_ = $p;
				return call_user_func_array(array($this,(method_exists($this,$m=('__'.$c.'_'.$p.'__')) ? $m : $am)),$args);
			}
		}
		throw new \ErrorException(get_class($this).'::'.$n.' method not found');
	}
	public function __destruct(){
		if(method_exists($this,'__del__')) $this->__del__();
	}
	public function __toString(){
		return (method_exists($this,'__str__')) ? (string)$this->__str__() : get_class($this);
	}
	/**
	 * アクセス可能なプロパティを取得する
	 * @param boolean $format
	 * @return mixed{}
	 */
	public function props($format=false){
		$r = array();
		foreach(array_keys(get_object_vars($this)) as $n){
			if($n[0] != '_'){
				$r[$n] = ($format) ? $this->{'fm_'.$n}() : $this->{$n}();
			}
		}
		return $r;
	}
	/**
	 * プロパティに値をセットする
	 * @param string $name
	 * @param string $type
	 * @param mixed $value
	 * @throws \InvalidArgumentException
	 */
	protected function set_prop($name,$type,$value){
		try{
			$c = get_class($this);
			return \ebi\Validation::set($type,$value,isset(self::$_m[$c][$name]) ? self::$_m[$c][$name] : []);
		}catch(\InvalidArgumentException $e){
			throw new \InvalidArgumentException($this->_.' must be an '.$type);
		}
	}
	private function ___get___(){
		if($this->prop_anon($this->_,'get') === false){
			throw new \InvalidArgumentException('not permitted');
		}
		if($this->prop_anon($this->_,'attr') !== null){
			return (is_array($this->{$this->_})) ? $this->{$this->_} : (is_null($this->{$this->_}) ? array() : array($this->{$this->_}));
		}
		return $this->{$this->_};
	}
	private function ___set___($v){
		if($this->prop_anon($this->_,'set') === false){
			throw new \InvalidArgumentException('not permitted');
		}
		$t = $this->prop_anon($this->_,'type');
		switch($this->prop_anon($this->_,'attr')){
			case 'a':
				foreach(func_get_args() as $a){
					$this->{$this->_}[] = $this->set_prop($this->_,$t,$a);
				}
				break;
			case 'h':
				$v = (func_num_args() === 2) ? [func_get_arg(0)=>func_get_arg(1)] : (is_array($v) ? $v : [(string)$v=>$v]);
				foreach($v as $k => $a){
					$this->{$this->_}[$k] = $this->set_prop($this->_,$t,$a);
				}
				break;
			default:
				$this->{$this->_} = $this->set_prop($this->_,$t,$v);
		}
		return $this;
	}
	private function ___rm___(){
		if($this->prop_anon($this->_,'set') === false) throw new \InvalidArgumentException('not permitted');
		if($this->prop_anon($this->_,'attr') === null){
			$this->{$this->_} = null;
		}else{
			if(func_num_args() == 0){
				$this->{$this->_} = array();
			}else{
				foreach(func_get_args() as $k) unset($this->{$this->_}[$k]);
			}
		}
	}
	private function ___fm___($f=null,$d=null){
		$p = $this->_;
		$v = (method_exists($this,$m=('__get_'.$p.'__'))) ? call_user_func(array($this,$m)) : $this->___get___();
		switch($this->prop_anon($p,'type')){
			case 'timestamp': return ($v === null) ? null : (date((empty($f) ? 'Y/m/d H:i:s' : $f),(int)$v));
			case 'date': return ($v === null) ? null : (date((empty($f) ? 'Y/m/d' : $f),(int)$v));
			case 'time':
				if($v === null) return 0;
				$h = floor($v / 3600);
				$i = floor(($v - ($h * 3600)) / 60);
				$s = floor($v - ($h * 3600) - ($i * 60));
				$m = str_replace(' ','0',rtrim(str_replace('0',' ',(substr(($v - ($h * 3600) - ($i * 60) - $s),2,12)))));
				return (($h == 0) ? '' : $h.':').(sprintf('%02d:%02d',$i,$s)).(($m == 0) ? '' : '.'.$m);
			case 'intdate': if($v === null) return null;
							return str_replace(array('Y','m','d'),array(substr($v,0,-4),substr($v,-4,2),substr($v,-2,2)),(empty($f) ? 'Y/m/d' : $f));
			case 'boolean': return ($v) ? (isset($d) ? $d : 'true') : (empty($f) ? 'false' : $f);
		}
		return $v;
	}
	private function ___ar___($i=null,$j=null){
		$v = $this->___get___();
		$a = is_array($v) ? $v : (($v === null) ? array() : array($v));
		if(isset($i)){
			$c = 0;
			$l = ((isset($j) ? $j : sizeof($a)) + $i);
			$r = array();
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
		}
		return (boolean)(($this->prop_anon($this->_,'type') == 'boolean') ? $v : isset($v));
	}
}
