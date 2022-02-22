<?php
namespace ebi;

trait Plugin{
	private static $_plug_funcs = [];
	private $_obj_plug_funcs = [];	
	
	/**
	 * クラスにプラグインをセットする
	 * @param mixed $o string / object / callable
	 * @param $n callableの場合のみplugin名
	 */
	public static function set_class_plugin($o,?string $n=null): void{
		if(is_array($o)){
			foreach($o as $c => $plugins){
				$r = new \ReflectionClass($c);
				
				if(in_array(__CLASS__,$r->getTraitNames())){
					foreach($plugins as $p){
						call_user_func_array([$r->getName(),'set_class_plugin'],[$p]);
					}
				}
			}
		}else{
			$g = get_called_class();
			if(is_string($o) && class_exists($o)){
				$o = new $o();
			}
			$t = (is_object($o) ? 1 : 0) + (is_callable($o) ? 2 : 0);
			
			if($t === 1){
				self::$_plug_funcs[$g][] = $o;
			}else if($t === 3 && !empty($n)){
				self::$_plug_funcs[$g][] = [$o,(string)$n];
			}
		}
	}
	/**
	 * オブジェクトにプラグインをセットする
	 * @param mixed $o
	 */
	public function set_object_plugin($o, ?string $n=null): void{
		if(is_string($o) && class_exists($o)){
			$o = new $o();	
		}
		$t = (is_object($o) ? 1 : 0) + (is_callable($o) ? 2 : 0);
		
		if($t === 1){
			$this->_obj_plug_funcs[] = $o;
		}else if($t === 3 && !empty($n)){
			$this->_obj_plug_funcs[] = [$o,(string)$n];
		}
	}
	/**
	 * クラスにプラグインがセットされているか
	 */
	protected static function has_class_plugin(string $n): bool{
		$g = get_called_class();
		foreach(\ebi\Conf::get_class_plugin($g) as $o){
			static::set_class_plugin($o);
		}
		if(isset(self::$_plug_funcs[$g])){
			foreach(self::$_plug_funcs[$g] as $o){
				if(is_array($o)){
					if($n == $o[1]) return true;
				}else if(method_exists($o,$n)){
					return true;
				}
			}
		}
		return false;
	}
	/**
	 * オブジェクトにプラグインがセットされているか
	 */
	protected function has_object_plugin(string $n): bool{
		if(static::has_class_plugin($n)) return true;
		foreach($this->_obj_plug_funcs as $o){
			if(is_array($o)){
				if($n == $o[1]) return true;
			}else if(method_exists($o,$n)){
				return true;
			}
		}
		return false;
	}
	/**
	 * クラスのプラグインを取得
	 */
	protected static function get_class_plugin_funcs(string $n): array{
		$rtn = [];
		$g = get_called_class();
		foreach(\ebi\Conf::get_class_plugin($g) as $o){
			static::set_class_plugin($o);
		}
		if(isset(self::$_plug_funcs[$g])){
			foreach(self::$_plug_funcs[$g] as $o){
				if(is_array($o)){
					if($n == $o[1]) $rtn[] = $o[0];
				}else if(method_exists($o,$n)){
					$rtn[] = [$o,$n];
				}
			}
		}
		return $rtn;
	}
	/**
	 * オブジェクトのプラグインを取得
	 */
	protected function get_object_plugin_funcs(string $n): array{
		$rtn = static::get_class_plugin_funcs($n);
		foreach($this->_obj_plug_funcs as $o){
			if(is_array($o)){
				if($n == $o[1]) $rtn[] = $o[0];
			}else if(method_exists($o,$n)){
				$rtn[] = [$o,$n];
			}
		}
		return $rtn;
	}
	/**
	 * クラスのプラグインをすべて実行する
	 * @return mixed
	 */
	protected static function call_class_plugin_funcs(string $n, ...$args){
		$r = null;
		
		foreach(static::get_class_plugin_funcs($n) as $o){
			$r = call_user_func_array($o, $args);
		}
		return $r;
	}
	/**
	 * クラスのプラグインを実行する
	 * @return mixed
	 */
	protected static function call_class_plugin_func(string $n, ...$args){
		$plugins = static::get_class_plugin_funcs($n);
		
		if(!empty($plugins)){
			return call_user_func_array(array_pop($plugins), $args);
		}
		return null;
	}
	/**
	 * オブジェクトのプラグインをすべて実行する
	 * @return mixed
	 */
	protected function call_object_plugin_funcs(string $n, ...$args){
		$r = null;
		foreach($this->get_object_plugin_funcs($n) as $o){
			$r = call_user_func_array($o, $args);
		}
		return $r;
	}
	/**
	 * オブジェクトのプラグインを実行する
	 * @return mixed
	 */
	protected function call_object_plugin_func(string $n, ...$args){
		$plugins = $this->get_object_plugin_funcs($n);
		
		if(!empty($plugins)){
			return call_user_func_array(array_pop($plugins), $args);
		}
		return null;
	}
	/**
	 * 関数を指定して実行する
	 * @return mixed
	 */
	protected static function call_func(callable $callable, ...$args){
		return call_user_func_array($callable, $args);
	}
}