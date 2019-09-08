<?php
namespace ebi;
/**
 * アノテーション
 * @author tokushima
 *
 */
class Annotation{
	/**
	 * クラスのアノテーションを取得する
	 * @param string $class 対象のクラス名
	 * @param string[] $names デコード対象のアノテーション名
	 * @param string $doc_name 説明を取得する場合の添字
	 * @param string $parent 遡る最上のクラス名
	 */
	public static function get_class($class,$names,$doc_name=null,$parent=null){
		$return = [];
		$t = new \ReflectionClass($class);
		$d = null;
		
		if(empty($parent)){
			$parent = 'stdClass';
		}
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
			$return[$name] = self::decode($d, $name, $doc_name);
		}
		return is_array($names) ? $return : $return[$names];
	}

	/**
	 * メソッドのアノテーションを取得する
	 * @param string $class 対象のクラス名
	 * @param string $method 対象のメソッド名
	 * @param string[] $names デコード対象のアノテーション名
	 * @param string $doc_name 説明を取得する場合の添字
	 */
	public static function get_method($class,$method,$names,$doc_name=null){
		$return = [];
		$t = new \ReflectionMethod($class,$method);
		$d = $t->getDocComment();
		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$d));
		
		foreach(is_array($names) ? $names : [$names] as $name){
			$return[$name] = self::decode($d, $name, $doc_name);
		}
		return is_array($names) ? $return : $return[$names];
	}
	
	private static function decode($d,$name,$doc_name=null){
		$result = null;
		$mtc = $m = [];
		
		if(preg_match_all('/@'.$name.'(.*)/',$d,$mtc)){
			$result = [];
			
			foreach($mtc[1] as $mc){
				if(!empty($mc) && ($mc[0] == ' ' || $mc[0] == "\t")){
					$at = strpos($mc,'@[');
					
					if($at === false && strpos($mc,'$') === false){
						$result['value'] = trim($mc);
					}else{
						$as = (false !== $at) ? substr($mc,$at+1,strrpos($mc,']')-$at) : null;
						
						try{
							$decode = self::activation($as);
						}catch(\ParseError $e){
							throw new \ebi\exception\InvalidAnnotationException('annotation error : `'.$mc.'`');
						}						
						if(preg_match("/([\\\.\w_]+[\[\]\{\}]*)\s\\\$([\w_]+)(.*)/",$mc,$m)){
							$n = $m[2];
							$result[$n] = (isset($result[$n])) ? array_merge($result[$n],$decode) : $decode;
							list($result[$n]['type'],$result[$n]['attr']) = (false != ($h = strpos($m[1],'{}')) || false !== strpos($m[1],'[]')) ? 
																				[substr($m[1],0,-2),(isset($h) && $h !== false) ? 'h' : 'a'] : 
																				[$m[1],null];
	
							if(!empty($doc_name)){
								$doc = trim(($at === false) ? $m[3] : substr($m[3],0,strpos($m[3],'@[')));
								
								if(!empty($doc)){
									$result[$n][$doc_name] = $doc;
								}
							}
							if(!ctype_lower($t=$result[$n]['type'])){
								if(!class_exists($t)){
									throw new \ebi\exception\InvalidArgumentException($t.' '.$result[$n]['type'].' not found');
								}
								$result[$n]['type'] = $t;
							}
						}else{
							$result = array_merge($result,$decode);
						}
					}
				}
			}
		}
		return $result;
	}
	/**
	 * アノテーション文字列の有効化
	 * @param  string $s アノテーション文字列
	 * @throws \ebi\exception\InvalidArgumentException 有効化に失敗した
	 * @return mixed{} アノテーションの連想配列
	 */
	public static function activation($s){
		if(empty($s)){
			return [];
		}
		$d = @eval('return '.$s.';');
		if(!is_array($d)){
			throw new \ebi\exception\InvalidArgumentException('annotation error : `'.$s.'`');
		}
		return $d;
	}
}