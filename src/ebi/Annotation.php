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
	 * @param string[] $names デコード対象のアノテーション名
	 * @throws \InvalidArgumentException
	 */
	public static function decode($class,$names,$parent='stdClass'){
		$return = [];
		
		$t = new \ReflectionClass($class);
		$d = null;
		while($t->getName() != $parent){
			$d = $t->getDocComment().$d;
			$t = $t->getParentClass();
			if($t === false) break;
		}
		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$d));
		
		foreach(is_array($names) ? $names : [$names] as $name){
			$result = null;
			if(preg_match_all("/@".$name."\s([\\\.\w_]+[\[\]\{\}]*)\s\\\$([\w_]+)(.*)/",$d,$m)){
				$result = [];
				
				foreach($m[2] as $k => $n){
					$as = (false !== ($s=strpos($m[3][$k],'@['))) ? substr($m[3][$k],$s+1,strrpos($m[3][$k],']')-$s) : null;
					$decode = self::activation($as);
					$result[$n] = (isset($result[$n])) ? array_merge($result[$n],$decode) : $decode;
					list($result[$n]['type'],$result[$n]['attr']) = (false != ($h = strpos($m[1][$k],'{}')) || false !== strpos($m[1][$k],'[]')) ? [substr($m[1][$k],0,-2),(isset($h) && $h !== false) ? 'h' : 'a'] : [$m[1][$k],null];
	
					if(!ctype_lower($t=$result[$n]['type'])){
						if($t[0]!='\\') $t='\\'.$t;
						if(!class_exists($t=str_replace('.','\\',$t))) throw new \InvalidArgumentException($t.' '.$result[$n]['type'].' not found');
						$result[$n]['type'] = (($t[0] !== '\\') ? '\\' : '').str_replace('.','\\',$t);
					}
				}
			}else if(preg_match_all("/@".$name."[\s]*(\[.*\])/",$d,$m)){
				$result = [];
				
				foreach($m[1] as $j){
					$decode = self::activation($j);
					$result = array_merge($result,$decode);
				}
			}
			$return[$name] = $result;
		}
		return is_array($names) ? $names : $return[$names];
	}
	public static function activation($s){
		if(empty($s)){
			return [];
		}
		$d = @eval('return '.$s.';');
		if(!is_array($d)){
			throw new \InvalidArgumentException('annotation error : `'.$s.'`');
		}
		return $d;
	}
}