<?php
namespace ebi;

trait Plugin{
	private static $_plug_funcs = [];
	private $_obj_plug_funcs = [];	
	
	/**
	 * クラスにプラグインをセットする
	 * @param object $o
	 * @param string $n
	 */
	public static function set_class_plugin($o,$n=null){
		if(is_array($o)){
			foreach($o as $c => $plugins){
				$r = new \ReflectionClass('\\'.str_replace('.','\\',$c));
				if(in_array(__CLASS__,$r->getTraitNames())){
					foreach($plugins as $p){
						call_user_func_array([$r->getName(),'set_class_plugin'],[$p]);
					}
				}
			}
		}else{
			$g = get_called_class();
			if(is_string($o) && class_exists(($c='\\'.str_replace('.','\\',$o)))) $o = new $c();		
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
	 * @param object $o
	 * @param string $n
	 */
	public function set_object_plugin($o,$n=null){
		if(is_string($o) && class_exists(($c='\\'.str_replace('.','\\',$o)))){
			$o = new $c();	
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
	 * @param string $n
	 * @return boolean
	 */
	protected static function has_class_plugin($n){
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
	 * @param string $n
	 * @return boolean
	 */
	protected function has_object_plugin($n){
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
	 * @param string $n
	 * @return array
	 */
	protected static function get_class_plugin_funcs($n){
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
	 * @param string $n
	 * @return array
	 */
	protected function get_object_plugin_funcs($n){
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
	 * @param string $n
	 * @return mixed
	 */
	protected static function call_class_plugin_funcs($n){
		$r = null;
		$a = func_get_args();
		array_shift($a);
		
		foreach(static::get_class_plugin_funcs($n) as $o){
			$r = call_user_func_array($o,$a);
		}
		return $r;
	}
	/**
	 * クラスのプラグインを実行する
	 * @param string $n
	 * @return mixed
	 */
	protected static function call_class_plugin_func($n){
		$plugins = static::get_class_plugin_funcs($n);
		
		if(!empty($plugins)){
			$a = func_get_args();
			array_shift($a);
			
			return call_user_func_array(array_pop($plugins), $a);
		}
		return null;
	}
	/**
	 * オブジェクトのプラグインをすべて実行する
	 * @param string $n
	 * @return mixed
	 */
	protected function call_object_plugin_funcs($n){
		$r = null;
		$a = func_get_args();
		array_shift($a);
		
		foreach($this->get_object_plugin_funcs($n) as $o){
			$r = call_user_func_array($o,$a);
		}
		return $r;
	}
	/**
	 * オブジェクトのプラグインを実行する
	 * @param string $n
	 */
	protected function call_object_plugin_func($n){
		$plugins = $this->get_object_plugin_funcs($n);
		
		if(!empty($plugins)){
			$a = func_get_args();
			array_shift($a);
			
			return call_user_func_array(array_pop($plugins), $a);
		}
		return null;
	}
	/**
	 * 関数を指定して実行する
	 * @param callable $o
	 * @return mixed
	 */
	protected static function call_func($o){
		if(!is_callable($o)) return;
		$a = func_get_args();
		array_shift($a);
		return call_user_func_array($o,$a);
	}
}