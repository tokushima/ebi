<?php
namespace ebi;
/**
 * アノテーション
 * @author tokushima
 *
 */
class Annotation{
	/**
	 * アノテーション文字列をデコードする
	 * @param text $d デコード対象となる文字列
	 * @param string $name デコード対象のアノテーション名
	 * @throws \InvalidArgumentException
	 */
	public static function decode($class,$name,$parent='stdClass'){
		$d = null;
		$t = new \ReflectionClass($class);
		while($t->getName() != $parent){
			$d = $t->getDocComment().$d;
			$t = $t->getParentClass();
			if($t === false) break;
		}
		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(array('/'.'**','*'.'/'),'',$d));
		
		$result = array();
		$decode_func = function($s){
			if(empty($s)) return array();
			$d = @eval('return '.$s.';');
			if(!is_array($d)) throw new \InvalidArgumentException('annotation error : `'.$s.'`');
			return $d;
		};
		if(preg_match_all("/@".$name."\s([\\\.\w_]+[\[\]\{\}]*)\s\\\$([\w_]+)(.*)/",$d,$m)){
			foreach($m[2] as $k => $n){
				$as = (false !== ($s=strpos($m[3][$k],'@['))) ? substr($m[3][$k],$s+1,strrpos($m[3][$k],']')-$s) : null;
				$decode = $decode_func($as);
				$result[$n] = (isset($result[$n])) ? array_merge($result[$n],$decode) : $decode;
				list($result[$n]['type'],$result[$n]['attr']) = (false != ($h = strpos($m[1][$k],'{}')) || false !== strpos($m[1][$k],'[]')) ? array(substr($m[1][$k],0,-2),(isset($h) && $h !== false) ? 'h' : 'a') : array($m[1][$k],null);
				if(!ctype_lower($t=$result[$n]['type'])){
					if($t[0]!='\\') $t='\\'.$t;
					if(!class_exists($t=str_replace('.','\\',$t))) throw new \InvalidArgumentException($t.' '.$result[$n]['type'].' not found');
					$result[$n]['type'] = (($t[0] !== '\\') ? '\\' : '').str_replace('.','\\',$t);
				}
			}
		}else if(preg_match_all("/@".$name."[\s]*(\[.*\])/",$d,$m)){
			foreach($m[1] as $j){
				$decode = $decode_func($j);
				$result = array_merge($result,$decode);
			}
		}
		return $result;
	}	
}