<?php
namespace ebi;

class Args{
	static private $opt = [];
	static private $value = [];

	/**
	 * 初期化
	*/
	public static function init(){
		$opt = $value = [];
		$argv = array_slice((isset($_SERVER['argv']) ? $_SERVER['argv'] : []),1);
			
		for($i=0;$i<sizeof($argv);$i++){
			if(substr($argv[$i],0,2) == '--'){
				$opt[substr($argv[$i],2)][] = ((isset($argv[$i+1]) && $argv[$i+1][0] != '-') ? $argv[++$i] : true);
			}else if(substr($argv[$i],0,1) == '-'){
				$keys = str_split(substr($argv[$i],1),1);
				if(count($keys) == 1){
					$opt[$keys[0]][] = ((isset($argv[$i+1]) && $argv[$i+1][0] != '-') ? $argv[++$i] : true);
				}else{
					foreach($keys as $k){
						$opt[$k][] = true;
					}
				}
			}else{
				$value[] = $argv[$i];
			}
		}
		self::$opt = $opt;
		self::$value = $value;
	}
	/**
	 * オプション値の取得
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public static function opt($name,$default=false){
		return array_key_exists($name,self::$opt) ? self::$opt[$name][0] : $default;
	}
	/**
	 * オプションが宣言されたか
	 * @param string $name
	 * @return boolean
	 */
	public static function has_opt($name){
		return array_key_exists($name,self::$opt);		
	}
	/**
	 * 引数の取得
	 * @param string $default
	 * @return string
	 */
	public static function value($default=null){
		return isset(self::$value[0]) ? self::$value[0] : $default;
	}
	/**
	 * オプション値を配列として取得
	 * @param string $name
	 * @return multitype:
	 */
	public static function opts($name){
		return array_key_exists($name,self::$opt) ? self::$opt[$name] : [];
	}
	/**
	 * 引数を全て取得
	 * @return string[]
	 */
	public static function values(){
		return self::$value;
	}
}
