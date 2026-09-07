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
		// var は継承順で階層マージする（trait→親→子、各段 doc→attr の順で後段が上書き）。
		if(in_array('var', $names, true)){
			$return['var'] = self::resolve_var_hierarchical($class, $parent_class, $doc_name);
			self::apply_native_type_completion($class, $return['var']);
		}
		return is_array($anon_names) ? $return : $return[$anon_names];
	}

	/**
	 * var メタを継承順（最も非特化→特化）で階層マージして解決する。
	 * 各クラス段の並びは trait(doc→attr) → クラス自身(doc→attr)。クラス連鎖は親→子。
	 * 後段（より特化）が前段を array_replace_recursive で上書きする。
	 * これにより doc/Attribute を問わず「継承側（消費クラス）が上位を上書き」できる。
	 */
	private static function resolve_var_hierarchical($class, ?string $parent_class, ?string $doc_name): ?array{
		$key = (is_object($class) ? get_class($class) : $class).'::__varh__::'.($parent_class ?? '').'::'.($doc_name ?? '');
		if(array_key_exists($key, self::$attr_cache)){
			return self::$attr_cache[$key];
		}
		try{
			$r = new \ReflectionClass($class);
		}catch(\Throwable $e){
			return null;
		}
		if(empty($parent_class)){
			$parent_class = 'stdClass';
		}
		$chain = [];
		$t = $r;
		while($t !== false && $t->getName() !== $parent_class){
			$chain[] = $t;
			$t = $t->getParentClass();
		}
		$chain = array_reverse($chain); // 親 → 子（非特化→特化）

		$acc = [];
		$merge = function(?array $site) use (&$acc){
			if(empty($site)){
				return;
			}
			foreach($site as $name => $data){
				$acc[$name] = isset($acc[$name]) ? array_replace_recursive($acc[$name], $data) : $data;
			}
		};
		foreach($chain as $c){
			$traits = self::all_traits($c);
			// trait 群（当該クラスより非特化）: doc → attr
			foreach($traits as $trait){
				$merge(self::decode_class_doc($trait, $doc_name));
			}
			$level_trait_attr = [];
			foreach($traits as $trait){
				$ta = [];
				self::collect_property_attributes($trait, $ta);
				foreach($ta as $n => $d){
					$level_trait_attr[$n] = $d;
				}
				$merge($ta);
			}
			// クラス自身: doc → attr（trait を上書き）
			$merge(self::decode_class_doc($c, $doc_name));
			$flat = [];
			self::collect_property_attributes($c, $flat);
			$own = [];
			foreach($c->getProperties() as $prop){
				if($prop->getDeclaringClass()->getName() !== $c->getName()){
					continue; // 親から継承した宣言は親の段で処理済み
				}
				$n = $prop->getName();
				if(!isset($flat[$n])){
					continue;
				}
				// trait flatten 分（再宣言でなく trait 由来）は除外。再宣言 override は data 差異で残る。
				if(isset($level_trait_attr[$n]) && $level_trait_attr[$n] === $flat[$n]){
					continue;
				}
				$own[$n] = $flat[$n];
			}
			$merge($own);
		}
		$result = empty($acc) ? null : $acc;
		self::$attr_cache[$key] = $result;
		return $result;
	}

	/**
	 * 当該クラス/トレイト「自身の」DocComment から var アノテーションを取得する（継承は含めない）。
	 */
	private static function decode_class_doc(\ReflectionClass $c, ?string $doc_name): ?array{
		$d = $c->getDocComment();
		if($d === false){
			return null;
		}
		$d = preg_replace("/^[\s]*\*[\s]{0,1}/m", '', str_replace(['/'.'**', '*'.'/'], '', $d));
		return self::decode($d, 'var', $doc_name);
	}

	/**
	 * 使用トレイトを再帰収集する（ネストした trait は非特化として先に並べる）。
	 */
	private static function all_traits(\ReflectionClass $c): array{
		$out = [];
		foreach($c->getTraits() as $trait){
			foreach(self::all_traits($trait) as $nested){
				$out[$nested->getName()] = $nested;
			}
			$out[$trait->getName()] = $trait;
		}
		return array_values($out);
	}

	/**
	 * PHP の宣言型(scalar)から var メタの type を補完する。Obj/Dao 共通。
	 * type が未解決(VarAttr/@var/命名規約のいずれでも未指定)で、かつプロパティに
	 * ReflectionNamedType(int/float/string/bool)が付いている時のみ補完する。
	 *
	 * 型宣言が無い(mixed/typeless)プロパティは触らない＝従来どおり \ebi\Validator は
	 * mixed 素通し(Dao 列パスは \ebi\Dao の局所 string 既定に委ねる)。array/クラス型/union は
	 * 曖昧(要素型・単一行/複数行・serial/datetime 等)なため補完しない＝必要なら VarAttr で明示する。
	 * これにより Obj/Dao runtime も SourceAnalyzer も同一の解決済みメタを読む（型解決の単一ソース）。
	 */
	private static function apply_native_type_completion(string $class, ?array &$var): void{
		static $map = ['int'=>'int','float'=>'float','string'=>'string','bool'=>'bool'];
		try{
			$r = new \ReflectionClass($class);
		}catch(\Throwable $e){
			return;
		}
		foreach($r->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $prop){
			if($prop->isStatic()){
				continue;
			}
			$name = $prop->getName();
			if($name === '' || $name[0] === '_' || !empty($var[$name]['type'])){
				continue;
			}
			$ref_type = $prop->getType();
			if($ref_type instanceof \ReflectionNamedType && isset($map[$ref_type->getName()])){
				if($var === null){
					$var = [];
				}
				$var[$name]['type'] = $map[$ref_type->getName()];
			}
		}
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
				case 'flow_token':
					$attrs = $r->getAttributes(\ebi\Attribute\FlowToken::class);
					if(!empty($attrs)){
						$result[$name] = [];
						foreach($attrs as $attr){
							$inst = $attr->newInstance();
							$result[$name][] = array_filter([
								'token' => $inst->token,
								'kind' => $inst->kind,
								'summary' => $inst->summary,
								'ambient' => $inst->ambient,
							], fn($v) => $v !== null && $v !== false);
						}
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
					$attrs = $r->getAttributes(\ebi\Attribute\Route::class);
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
							$data['type'] = $inst->type instanceof \ebi\T ? $inst->type->value : $inst->type;
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
							$data['type'] = $inst->type instanceof \ebi\T ? $inst->type->value : $inst->type;
							$result[$name][$n] = $data;
						}
					}
					break;
				case 'error_response':
					$attrs = $r->getAttributes(\ebi\Attribute\ErrorResponse::class);
					if(!empty($attrs)){
						$result[$name] = [];
						foreach($attrs as $attr){
							$inst = $attr->newInstance();
							$result[$name][] = [
								'status' => $inst->status,
								'description' => $inst->description,
							];
						}
					}
					break;
				case 'produces':
					$attrs = $r->getAttributes(\ebi\Attribute\Produces::class);
					if(!empty($attrs)){
						$result[$name] = [];
						foreach($attrs as $attr){
							$inst = $attr->newInstance();
							$result[$name][] = array_filter([
								'token' => $inst->token,
								'via' => $inst->via,
								'when' => $inst->when,
								'summary' => $inst->summary,
								'kind' => $inst->kind,
							], fn($v) => $v !== null);
						}
					}
					break;
				case 'requires':
					$attrs = $r->getAttributes(\ebi\Attribute\Requires::class);
					if(!empty($attrs)){
						$result[$name] = [];
						foreach($attrs as $attr){
							$inst = $attr->newInstance();
							$data = ['token' => $inst->token, 'optional' => $inst->optional];
							if($inst->bind !== null){
								$data['bind'] = $inst->bind;
							}
							if($inst->summary !== null){
								$data['summary'] = $inst->summary;
							}
							$result[$name][] = $data;
						}
					}
					break;
				case 'follows':
					$attrs = $r->getAttributes(\ebi\Attribute\Follows::class);
					if(!empty($attrs)){
						$result[$name] = [];
						foreach($attrs as $attr){
							$inst = $attr->newInstance();
							$data = ['endpoint' => $inst->endpoint, 'soft' => $inst->soft];
							if($inst->summary !== null){
								$data['summary'] = $inst->summary;
							}
							$result[$name][] = $data;
						}
					}
					break;
				case 'batch':
					$attrs = $r->getAttributes(\ebi\Attribute\Batch::class);
					if(!empty($attrs)){
						$inst = $attrs[0]->newInstance();
						$result[$name] = array_filter([
							'name' => $inst->name,
						], fn($v) => $v !== null);
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
				$ref_type = $prop->getType();
				// type: を明示したか（items: だけの指定も型指定扱い）。未指定なら type を metadata に
				// 載せず、SourceAnalyzer / Dao 側の PHP 宣言型からの解決に委ねる。
				$type_specified = ($type !== '' || $inst->items !== null);

				// type 未指定時も配列/ハッシュ判定のために PHP の型宣言から解決値を持つ
				if($type === ''){
					$type = ($ref_type instanceof \ReflectionNamedType) ? $ref_type->getName() : 'string';
				}

				// nullable 未指定時は PHP の型宣言から推論（型宣言なしはnullable扱い）
				$nullable = $inst->nullable ?? (($ref_type === null) ? true : $ref_type->allowsNull());

				// 配列/ハッシュ型の処理
				if($type === 'array' && $inst->items !== null){
					$attr_type = 'a';
					$type = $inst->items;
				}else if(str_ends_with($type, '[]')){
					$attr_type = 'a';
					$type = substr($type, 0, -2);
				}else if(str_ends_with($type, '{}')){
					$attr_type = 'h';
					$type = substr($type, 0, -2);
				}

				$data = [];

				// type: を明示した時だけ metadata に載せる（未指定は PHP 宣言へ委譲）
				if($type_specified){
					$data['type'] = $type;
					if($attr_type !== null){
						$data['attr'] = $attr_type;
					}
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
				if(!$inst->expose){
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
				if(!$nullable){
					$data['nullable'] = false;
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
				if($inst->enum !== null){
					$data['enum'] = $inst->enum;
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
