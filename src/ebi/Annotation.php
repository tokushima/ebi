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
	 * @param string $class 対象のクラス名
	 * @param text $d デコード対象となる文字列
	 * @param string[] $names デコード対象のアノテーション名
	 * @param string $parent 遡る最上のクラス名
	 */
	public static function decode($class,$names,$parent='stdClass'){
		$return = [];
		
		$t = new \ReflectionClass($class);
		$d = null;
		
		while($t->getName() != $parent){
			$d = $t->getDocComment().$d;
			
			foreach($t->getTraits() as $trats){
				$d = $trats->getDocComment().$d;
			}
			$t = $t->getParentClass();
			if($t === false){
				break;
			}
		}
		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$d));
		
		foreach(is_array($names) ? $names : [$names] as $name){
			$result = null;
			
			if(preg_match_all("/@".$name."(.*)/",$d,$mtc)){
				$result = [];
				
				foreach($mtc[1] as $mc){
					if(!empty($mc) && ($mc[0] == ' ' || $mc[0] == "\t")){
						$as = (false !== ($s=strpos($mc,'@['))) ? substr($mc,$s+1,strrpos($mc,']')-$s) : null;
						$decode = self::activation($as);						
						
						if(preg_match("/([\\\.\w_]+[\[\]\{\}]*)\s\\\$([\w_]+)(.*)/",$mc,$m)){
							$n = $m[2];
							$result[$n] = (isset($result[$n])) ? array_merge($result[$n],$decode) : $decode;
							list($result[$n]['type'],$result[$n]['attr']) = (false != ($h = strpos($m[1],'{}')) || false !== strpos($m[1],'[]')) ? 
																				[substr($m[1],0,-2),(isset($h) && $h !== false) ? 'h' : 'a'] : 
																				[$m[1],null];
							
							if(!ctype_lower($t=$result[$n]['type'])){
								if($t[0]!='\\') $t='\\'.$t;
								if(!class_exists($t=str_replace('.','\\',$t))) throw new \InvalidArgumentException($t.' '.$result[$n]['type'].' not found');
								$result[$n]['type'] = (($t[0] !== '\\') ? '\\' : '').str_replace('.','\\',$t);
							}
						}else{
							$result = array_merge($result,$decode);
						}
					}
				}
			}
			$return[$name] = $result;
		}
		return is_array($names) ? $return : $return[$names];
	}
	/**
	 * アノテーション文字列の有効化
	 * @param  string $s アノテーション文字列
	 * @throws \InvalidArgumentException 有効化に失敗した
	 * @return mixed{} アノテーションの連想配列
	 */
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