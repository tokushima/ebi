<?php
namespace ebi;

class AttributeReader{
	private static array $attr_cache = [];

	/**
	 * クラスのアノテーションを取得する（Attribute優先、DocBlockフォールバック）
	 * @param mixed $class (string|object)
	 * @param mixed $anon_names (string|array)
	 */
	public static function get_class($class, $anon_names, ?string $doc_name=null, ?string $parent_class=null): ?array{
		$names = is_array($anon_names) ? $anon_names : [$anon_names];
		$return = [];

		// Attribute読み取り
		$attr_result = self::get_class_attributes($class, $names, $parent_class);

		// DocBlock読み取り（フォールバック）
		$doc_result = self::get_class_docblock($class, $names, $doc_name, $parent_class);

		// マージ（Attribute優先）
		foreach($names as $name){
			$attr_val = $attr_result[$name] ?? null;
			$doc_val = $doc_result[$name] ?? null;

			if($attr_val !== null){
				if($doc_val !== null && is_array($attr_val) && is_array($doc_val)){
					// 配列の場合、DocBlockの値にAttributeの値を上書きマージ
					$return[$name] = array_replace_recursive($doc_val, $attr_val);
				}else{
					$return[$name] = $attr_val;
				}
			}else{
				$return[$name] = $doc_val;
			}
		}
		return is_array($anon_names) ? $return : $return[$anon_names];
	}

	/**
	 * メソッドのアノテーションを取得する（Attribute優先、DocBlockフォールバック）
	 * @param mixed $class (string|object)
	 * @param mixed $anon_names (string|array)
	 */
	public static function get_method($class, string $method, $anon_names, ?string $doc_name=null): ?array{
		$names = is_array($anon_names) ? $anon_names : [$anon_names];
		$return = [];

		// Attribute読み取り
		$attr_result = self::get_method_attributes($class, $method, $names);

		// DocBlock読み取り（フォールバック）
		$doc_result = self::get_method_docblock($class, $method, $names, $doc_name);

		// マージ（Attribute優先）
		foreach($names as $name){
			$attr_val = $attr_result[$name] ?? null;
			$doc_val = $doc_result[$name] ?? null;

			if($attr_val !== null){
				if($doc_val !== null && is_array($attr_val) && is_array($doc_val)){
					$return[$name] = array_replace_recursive($doc_val, $attr_val);
				}else{
					$return[$name] = $attr_val;
				}
			}else{
				$return[$name] = $doc_val;
			}
		}
		return is_array($anon_names) ? $return : $return[$anon_names];
	}

	/**
	 * クラスからAttributeを読み取る
	 */
	private static function get_class_attributes($class, array $names, ?string $parent_class): array{
		$result = [];
		$class_name = is_object($class) ? get_class($class) : $class;
		$cache_key = $class_name.'::'.implode(',', $names);

		if(isset(self::$attr_cache[$cache_key])){
			return self::$attr_cache[$cache_key];
		}

		$r = new \ReflectionClass($class);

		foreach($names as $name){
			$result[$name] = null;

			switch($name){
				case 'var':
					$result[$name] = self::get_property_attributes($r, $parent_class);
					break;
				case 'table':
					$attrs = $r->getAttributes(\ebi\Attribute\Table::class);
					if(!empty($attrs)){
						$inst = $attrs[0]->newInstance();
						$vars = get_object_vars($inst);
						$result[$name] = [];
						if($vars['name'] !== null){
							$result[$name]['name'] = $vars['name'];
						}
						if($vars['create'] !== true){
							$result[$name]['create'] = $vars['create'];
						}
					}
					break;
				case 'readonly':
					$attrs = $r->getAttributes(\ebi\Attribute\ReadonlyAttr::class);
					if(!empty($attrs)){
						$result[$name] = [];
					}
					break;
				case 'login':
					$attrs = $r->getAttributes(\ebi\Attribute\Login::class);
					if(!empty($attrs)){
						$inst = $attrs[0]->newInstance();
						$result[$name] = array_filter(get_object_vars($inst), fn($v) => $v !== null);
					}
					break;
				case 's2s':
					$attrs = $r->getAttributes(\ebi\Attribute\S2s::class);
					if(!empty($attrs)){
						$result[$name] = [];
					}
					break;
			}
		}

		self::$attr_cache[$cache_key] = $result;
		return $result;
	}

	/**
	 * メソッドからAttributeを読み取る
	 */
	private static function get_method_attributes($class, string $method, array $names): array{
		$result = [];
		$r = new \ReflectionMethod($class, $method);

		foreach($names as $name){
			$result[$name] = null;

			switch($name){
				case 'automap':
					$attrs = $r->getAttributes(\ebi\Attribute\Automap::class);
					if(!empty($attrs)){
						$inst = $attrs[0]->newInstance();
						$result[$name] = array_filter(get_object_vars($inst), fn($v) => $v !== null);
					}
					break;
				case 'http_method':
					$attrs = $r->getAttributes(\ebi\Attribute\HttpMethod::class);
					if(!empty($attrs)){
						$inst = $attrs[0]->newInstance();
						$result[$name] = ['value' => $inst->method];
					}
					break;
				case 'request':
					$attrs = $r->getAttributes(\ebi\Attribute\Parameter::class);
					if(!empty($attrs)){
						$result[$name] = [];
						foreach($attrs as $attr){
							$inst = $attr->newInstance();
							$n = $inst->name;
							$data = array_filter(get_object_vars($inst), fn($v) => $v !== null);
							unset($data['name']);
							$data['type'] = $inst->type;
							$result[$name][$n] = $data;
						}
					}
					break;
				case 'context':
					$attrs = $r->getAttributes(\ebi\Attribute\Response::class);
					if(!empty($attrs)){
						$result[$name] = [];
						foreach($attrs as $attr){
							$inst = $attr->newInstance();
							$n = $inst->name;
							$data = array_filter(get_object_vars($inst), fn($v) => $v !== null);
							unset($data['name']);
							$data['type'] = $inst->type;
							$result[$name][$n] = $data;
						}
					}
					break;
			}
		}
		return $result;
	}

	/**
	 * プロパティのAttributeを読み取る
	 */
	private static function get_property_attributes(\ReflectionClass $r, ?string $parent_class): ?array{
		$result = [];
		$classes = [$r];

		if(empty($parent_class)){
			$parent_class = 'stdClass';
		}

		// 親クラスを収集
		$t = $r;
		while(($parent = $t->getParentClass()) !== false && $parent->getName() !== $parent_class){
			$classes[] = $parent;
			$t = $parent;
		}

		// 逆順で処理（親から子へ）
		foreach(array_reverse($classes) as $class){
			// traitを先に処理
			foreach($class->getTraits() as $trait){
				self::collect_property_attributes($trait, $result);
			}
			self::collect_property_attributes($class, $result);
		}

		return empty($result) ? null : $result;
	}

	/**
	 * プロパティのAttributeを収集
	 */
	private static function collect_property_attributes(\ReflectionClass $class, array &$result): void{
		foreach($class->getProperties() as $prop){
			$attrs = $prop->getAttributes(\ebi\Attribute\VarAttr::class);

			if(!empty($attrs)){
				$inst = $attrs[0]->newInstance();
				$name = $prop->getName();

				$type = $inst->type;
				$attr_type = null;

				// 配列/ハッシュ型の処理
				if(str_ends_with($type, '[]')){
					$attr_type = 'a';
					$type = substr($type, 0, -2);
				}else if(str_ends_with($type, '{}')){
					$attr_type = 'h';
					$type = substr($type, 0, -2);
				}

				$data = [
					'type' => $type,
				];

				if($attr_type !== null){
					$data['attr'] = $attr_type;
				}
				if($inst->summary !== null){
					$data['summary'] = $inst->summary;
				}
				if($inst->primary){
					$data['primary'] = true;
				}
				if($inst->auto_now){
					$data['auto_now'] = true;
				}
				if($inst->auto_now_add){
					$data['auto_now_add'] = true;
				}
				if($inst->auto_code_add){
					$data['auto_code_add'] = true;
				}
				if(!$inst->hash){
					$data['hash'] = false;
				}
				if(!$inst->get){
					$data['get'] = false;
				}
				if(!$inst->set){
					$data['set'] = false;
				}
				if($inst->unique){
					$data['unique'] = true;
				}
				if($inst->unique_together !== null){
					$data['unique_together'] = $inst->unique_together;
				}
				if($inst->require){
					$data['require'] = true;
				}
				if($inst->min !== null){
					$data['min'] = $inst->min;
				}
				if($inst->max !== null){
					$data['max'] = $inst->max;
				}
				if($inst->cond !== null){
					$data['cond'] = $inst->cond;
				}
				if($inst->column !== null){
					$data['column'] = $inst->column;
				}
				if($inst->extra){
					$data['extra'] = true;
				}
				if($inst->ctype !== null){
					$data['ctype'] = $inst->ctype;
				}
				if($inst->base !== null){
					$data['base'] = $inst->base;
				}
				if($inst->length !== null){
					$data['length'] = $inst->length;
				}

				$result[$name] = $data;
			}
		}
	}

	/**
	 * DocBlockからクラスアノテーションを読み取る（従来の処理）
	 */
	private static function get_class_docblock($class, array $names, ?string $doc_name, ?string $parent_class): array{
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

		foreach($names as $name){
			$return[$name] = self::decode($d, $name, $doc_name);
		}
		return $return;
	}

	/**
	 * DocBlockからメソッドアノテーションを読み取る（従来の処理）
	 */
	private static function get_method_docblock($class, string $method, array $names, ?string $doc_name): array{
		$return = [];
		$t = new \ReflectionMethod($class, $method);
		$d = $t->getDocComment();
		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace(['/'.'**','*'.'/'],'',$d));

		foreach($names as $name){
			$return[$name] = self::decode($d, $name, $doc_name);
		}
		return $return;
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

	/**
	 * キャッシュをクリアする
	 */
	public static function clear_cache(): void{
		self::$attr_cache = [];
	}
}
