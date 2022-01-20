<?php
namespace ebi;

class Annotation{
	/**
	 * クラスのアノテーションを取得する
	 */
	public static function get_class(string|object $class, string|array $anon_names, ?string $doc_name=null, ?string $parent_class=null): ?array{
		$return = [];
		$t = new \ReflectionClass($class);
		$d = '';
		
		if(empty($parent_class)){
			$parent_class = 'stdClass';
		}
		while($t->getName() != $parent_class){
			$d = $t->getDocComment().$d;
			
			foreach($t->getTraits() as $trait){
				$d = $trait->getDocComment().$d;
			}
			$t = $t->getParentClass();
			if($t === false){
				break;
			}
		}

		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$d));
		
		foreach(is_array($anon_names) ? $anon_names : [$anon_names] as $name){
			$return[$name] = self::decode($d, $name, $doc_name);
		}
		return is_array($anon_names) ? $return : $return[$anon_names];
	}

	/**
	 * メソッドのアノテーションを取得する
	 */
	public static function get_method(string|object $class, string $method, string|array $anon_names, ?string $doc_name=null): ?array{
		$return = [];
		$t = new \ReflectionMethod($class,$method);
		$d = $t->getDocComment();
		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$d));
		
		foreach(is_array($anon_names) ? $anon_names : [$anon_names] as $name){
			$return[$name] = self::decode($d, $name, $doc_name);
		}
		return is_array($anon_names) ? $return : $return[$anon_names];
	}
	
	private static function decode(string $d, string $name,$doc_name=null): ?array{
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
						$as = (false !== $at) ? substr($mc,$at+1,strrpos($mc,']')-$at) : '';
						
						try{
							$decode = self::activation($as);
						}catch(\ParseError $e){
							throw new \ebi\exception\InvalidAnnotationException('annotation error : `'.$mc.'`');
						}						
						if(preg_match("/([\\\.\w_]+[\[\]\{\}]*)\s\\\$([\w_]+)(.*)/",$mc,$m)){
							$n = $m[2];
							$result[$n] = (isset($result[$n])) ? array_merge($result[$n],$decode) : $decode;
							[$result[$n]['type'], $result[$n]['attr']] = (
								false != ($h = strpos($m[1],'{}')) || 
								false !== strpos($m[1],'[]')
							) ? [substr($m[1],0,-2),(isset($h) && $h !== false) ? 'h' : 'a'] : [$m[1], null];
	
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
	 */
	public static function activation(string $s): array{
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