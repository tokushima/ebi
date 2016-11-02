<?php
namespace ebi;
/**
 * ソート
 * @author tokushima
 */
class Sorter{
	/**
	 * ハッシュのキーを指定してソート
	 *
	 * @param array $list
	 * @param string $key
	 * @return array
	 */
	public static function hash(array $list,$key){
		if(!empty($key) && is_string($key)){
			$revers = false;

			if($key[0] == '-'){
				$key = substr($key,1);
				$revers = true;
			}
			try{
				foreach($list as $o) $o[$key] = isset($o[$key]) ? $o[$key] : null;
				usort($list,create_function('$a,$b',sprintf('return ($a["%s"] %s $b["%s"]) ? -1 : 1;',$key,(($revers) ? '>' : '<'),$key)));
			}catch(\ErrorException $e){}
		}
		return $list;
	}	
	/**
	 * Objectのgetterを指定してソート
	 *
	 * @param array $list
	 * @param string $getter_name
	 * @return array
	 */
	public static function object(array $list,$getter_name){
		if(!empty($getter_name) && is_string($getter_name)){
			$revers = false;

			if($getter_name[0] == '-'){
				$getter_name = substr($getter_name,1);
				$revers = true;
			}
			try{
				foreach($list as $o) $o->$getter_name();
				usort($list,create_function('$a,$b',sprintf('return ($a->%s() %s $b->%s()) ? -1 : 1;',$getter_name,(($revers) ? '>' : '<'),$getter_name)));
			}catch(\ErrorException $e){}
		}
		return $list;
	}
	/**
	 * 文字列として比較してソート
	 *
	 * @param array $list
	 * @param boolean $revers
	 * @return array
	 */
	public static function string(array $list,$revers=false){
		uasort($list,create_function('$a,$b',sprintf('return (strcmp((string)($a),(string)($b)) > 0) ? %s1 : %s1;',(($revers) ? '-' : ''),(($revers) ? '' : '-'))));
		return $list;
	}

}
