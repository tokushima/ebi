<?php
namespace ebi;
/**
 * 定義情報を格納するクラス
 * @author tokushima
 */
class Conf{
	private static $value = [];
	private static $plugins = [];
	/**
	 * 定義情報をセットする
	 * @param string|array $class
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($class,$key=null,$value=null){
		if(is_array($class)){
			foreach($class as $c => $v){
				foreach($v as $k => $value){
					if(!isset(self::$value[$c]) || !array_key_exists($k,self::$value[$c])){
						self::$value[$c][$k] = $value;
					}
				}
			}
		}else if(!empty($key)){
			$class = str_replace("\\",'.',$class);
			if($class[0] === '.') $class = substr($class,1);
			if(func_num_args() > 3){
				$value = func_get_args();
				array_shift($value);
				array_shift($value);
			}
			if(!isset(self::$value[$class]) || !array_key_exists($key,self::$value[$class])) self::$value[$class][$key] = $value;
		}
	}
	/**
	 * 定義されているか
	 * @param string $class
	 * @param string $key
	 * @return boolean
	 */
	public static function exists($class,$key){
		return (isset(self::$value[$class]) && array_key_exists($key,self::$value[$class]));
	}
	/**
	 * 定義情報を取得する
	 * @param string $key
	 * @param mixed $default
	 */
	public static function get($key,$default=null,$return_vars=null){
		if(strpos($key,'@') === false){
			list(,$d) = debug_backtrace(false);
			$class = str_replace('\\','.',$d['class']);
			if($class[0] === '.') $class = substr($class,1);
			if(preg_match('/^(.+?\.[A-Z]\w*)/',$class,$m)) $class = $m[1];
		}else{
			list($class,$key) = explode('@',$key,2);
		}
		$result = self::exists($class,$key) ? self::$value[$class][$key] : $default;
		if(is_array($return_vars)){
			if(empty($return_vars) && !is_array($result)) return array($result);
			$result_vars = array();
			foreach($return_vars as $var_name) $result_vars[] = isset($result[$var_name]) ? $result[$var_name] : null;
			return $result_vars;
		}
		return $result;
	}
	/**
	 * アプリケーションの動作環境
	 * @return string
	 */
	public static function appmode(){
		return defined('APPMODE') ? constant('APPMODE') : null;
	}
	
	/**
	 * 作業ディレクトリのパス
	 * @param string $path
	 * @return string
	 */
	public static function work_path($path=null){
		$dir = self::get('work_dir');
		if(empty($dir)) $dir = defined('WORK_DIR') ? constant('WORK_DIR') : (getcwd().'/work/');
		$dir = str_replace("\\",'/',$dir);
		if(substr($dir,-1) != '/') $dir = $dir.'/';
		return $dir.$path;
	}
	/**
	 * リソースファイルのディレクトリパス
	 * @param string $path
	 * @return string
	 */
	public static function resource_path($path=null){
		$dir = self::get('resource_dir');
		if(empty($dir)) $dir = defined('RESOURCE_DIR') ? constant('RESOURCE_DIR') : (getcwd().'/resources/');
		$dir = str_replace("\\",'/',$dir);
		if(substr($dir,-1) != '/') $dir = $dir.'/';
		return $dir.$path;
	}
	/**
	 * Pluginに遅延セットする
	 * @param string $class
	 * @param string $obj
	 */
	public static function set_class_plugin($class,$obj=null){
		if(is_array($class)){
			foreach($class as $c => $v){
				$c = str_replace("\\",'.',$c);
				if($c[0] === '.') $c = substr($c,1);				
				
				foreach($v as $k => $value){
					if(!isset(self::$plugins[$c]) || !array_key_exists($k,self::$plugins[$c])){
						self::$plugins[$c][] = $value;
					}
				}
			}
		}else if(!empty($obj)){
			$class = str_replace("\\",'.',$class);
			if($class[0] === '.') $class = substr($class,1);
			if(!is_array($obj)){
				$obj = [$obj];
			}
			foreach($obj as $o){
				self::$plugins[$class][] = $o;
			}
		}
	}
	/**
	 * Pluginに遅延セットされたオブジェクトを返す
	 * @param string $class
	 * @return array
	 */
	public static function get_class_plugin($class){
		$class = str_replace('\\','.',$class);
		$rtn = [];
		if(isset(self::$plugins[$class])){
			$rtn = self::$plugins[$class];
			unset(self::$plugins[$class]);
		}
		return $rtn;
	}
}