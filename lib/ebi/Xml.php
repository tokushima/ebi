<?php
namespace ebi;

class Xml implements \IteratorAggregate{
	private array $attr = [];
	private array $plain_attr = [];
	private ?string $name;
	private ?string $value;
	private bool $close_empty = true;

	private ?string $plain = null;
	private int $pos = 0;
	private bool $esc = true;

	/**
	 * @param mixed $name string|object
	 * @param mixed $value string|array
	 */
	public function __construct($name=null, $value=null){
		if($value === null && is_object($name)){
			$n = explode('\\',get_class($name));
			$this->name = array_pop($n);
			$this->value($name);
		}else{
			$this->name = empty($name) ? $name : trim($name);
			$this->value($value);
		}
	}

	public function getIterator(): \Traversable{
		return new \ArrayIterator($this->attr);
	}
	/**
	 * 値が無い場合は閉じを省略する
	 */
	public function close_empty(bool $bool): self{
		$this->close_empty = $bool;
		return $this;
	}
	/**
	 * エスケープするか
	 */
	public function escape(bool $bool): self{
		$this->esc = $bool;
		return $this;
	}

	/**
	 * setできた文字列
	 */
	public function plain(): string{
		return $this->plain;
	}

	/**
	 * 子要素検索時のカーソル
	 */
	public function cur(): int{
		return $this->pos;
	}

	/**
	 * 要素名
	 */
	public function name(?string $name=null): ?string{
		if(isset($name)){
			$this->name = $name;
		}
		return $this->name;
	}

	/**
	 * @param mixed $v
	 */
	private function get_value($v): ?string{
		if($v instanceof self){
			$v = $v->get();
		}else if(is_bool($v)){
			$v = ($v) ? 'true' : 'false';
		}else if($v === ''){
			$v = null;
		}else if(is_array($v) || is_object($v)){
			$r = '';
			foreach($v as $k => $c){
				if($c instanceof self){
					$c->escape($this->esc);
					$r .= $c->get();
				}else{
					if(is_numeric($k) && is_object($c)){
						$e = explode('\\',get_class($c));
						$k = array_pop($e);
					}
					if(is_numeric($k)) $k = 'data';
					$x = new self($k,$c);
					$x->escape($this->esc);
					$r .= $x->get();
				}
			}
			$v = $r;
		}else if(!empty($v) && $this->esc && strpos($v,'<![CDATA[') === false && preg_match("/&|<|>|(\&[^#\da-zA-Z])/",$v)){
			$v = '<![CDATA['.$v.']]>';
		}
		return $v;
	}
	/**
	 * 値を設定、取得する
	 */
	public function value(...$args): ?string{
		if(sizeof($args) > 0){
			$this->value = $this->get_value($args[0]);
		}
		if(!empty($this->value) && strpos($this->value,'<![CDATA[') !== false){
			return preg_replace('/<!\[CDATA\[(.+)\]\]>/s','\\1',$this->value);
		}
		return $this->value;
	}
	/**
	 * 値を追加する
	 * ２つ目のパラメータがあるとアトリビュートの追加となる
	 */
	public function add(...$args): self{
		if(sizeof($args) === 2){
			$this->attr($args[0], $args[1]);
		}else{
			$this->value .= $this->get_value($args[0]);
		}
		return $this;
	}
	/**
	 * アトリビュートを取得する
	 */
	public function in_attr(string $name, ?string $default=null): ?string{
		return $this->attr[strtolower($name)] ?? $default;
	}
	/**
	 * アトリビュートから削除する
	 * パラメータが一つも無ければ全件削除
	 */
	public function rm_attr(...$args): void{
		if(sizeof($args) === 0){
			$this->attr = [];
		}else{
			foreach($args as $n){
				unset($this->attr[$n]);
			}
		}
	}
	/**
	 * アトリビュートがあるか
	 */
	public function is_attr(string $name): bool{
		return array_key_exists($name, $this->attr);
	}
	/**
	 * アトリビュートを設定
	 * @param mixed $value
	 */
	public function attr(string $name, $value): self{
		$this->attr[strtolower($name)] = is_bool($value) ? (($value) ? 'true' : 'false') : $value;
		return $this;
	}
	/**
	 * 値の無いアトリビュートを設定
	 */
	public function plain_attr(string $v): void{
		$this->plain_attr[] = $v;
	}
	/**
	 * XML文字列を返す
	 */
	public function get(?string $encoding=null, bool $format=false, string $indent_str="\t"): string{
		if($this->name === null){
			throw new \ebi\exception\NotFoundException('undef name');
		}
		$attr = '';
		$value = ($this->value === null || $this->value === '') ? null : (string)$this->value;

		if($format && !empty($value)){
			$value = "\n".self::format($value,$indent_str,1);
		}
		foreach(array_keys($this->attr) as $k){
			$attr .= ' '.$k.'="'.($this->esc ? htmlentities($this->in_attr($k),ENT_QUOTES,'UTF-8') : $this->in_attr($k)).'"';
		}
		return ((empty($encoding)) ? '' : '<?xml version="1.0" encoding="'.$encoding.'" ?'.'>'."\n")
				.('<'.$this->name.$attr.(implode(' ',$this->plain_attr)).(($this->close_empty && !isset($value)) ? ' /' : '').'>')
				.$value
				.((!$this->close_empty || isset($value)) ? sprintf('</%s>',$this->name) : '')
				.($format ? "\n" : '');
	}

	public function __toString(){
		return $this->get();
	}

	/**
	 * 検索する
	 * @param mixed string|array
	 */
	public function find($path=null, int $offset=0, int $length=0): \ebi\XmlIterator{
		if(is_string($path) && strpos($path,'/') !== false){
			[$name, $path] = explode('/',$path,2);

			foreach(new \ebi\XmlIterator($name, $this->value(), 0, 0) as $t){
				try{
					$it = $t->find($path,$offset,$length);
					if($it->valid()){
						if(is_array($it)){
							reset($it);
						}
						return $it;
					}
				}catch(\ebi\exception\NotFoundException $e){
				}
			}
			throw new \ebi\exception\NotFoundException();
		}
		return new \ebi\XmlIterator($path, $this->value(), $offset, $length);
	}

	/**
	 * 対象の件数
	 */
	public function find_count(string $name, int $offset=0, int $length=0): int{
		$cnt = 0;

		while($this->find($name,$offset,$length)){
			$cnt++;
		}
		return $cnt;
	}
	/**
	 * １件取得する
	 */
	public function find_get(string $path, int $offset=0): self{
		foreach($this->find($path,$offset,1) as $x){
			return $x;
		}
		throw new \ebi\exception\NotFoundException($path.' not found');
	}

	/**
	 * 置換して新規のインスタンスを返す
	 */
	public function replace(string $path, string $value): self{
		$list = [];
		$x = clone($this);
		foreach(explode('/',$path) as $p){
			$x = $x->find_get($p);
			array_unshift($list,$x);
		}
		$top = $list[sizeof($list)-1];
		$child = array_shift($list);
		$child->escape(false)->value($value);

		if(empty($list)){
			$parent = $child;
		}else{
			foreach($list as $parent){
				$parent->escape(false);
				$parent->value(str_replace($child->plain(),$child->get(),$parent->value()));
			}
		}
		$xml = clone($this);
		$xml->escape(false)->value(str_replace($top->plain(),$parent->get(),$xml->value()));
		return self::extract($xml->get(),$xml->name());
	}

	/**
	 * 子要素を展開する
	 * @return mixed string|array
	 */
	public function children(){
		$children = $arr = [];
		$bool = false;

		foreach($this->find() as $xml){
			$bool = true;
			$name = $xml->name();

			if(isset($children[$name])){
				if(!isset($arr[$name])){

					$children[$name] = [$children[$name]];
					$arr[$name] = true;
				}
				$children[$name][] = $xml->children();
			}else{
				$children[$name] = $xml->children();
			}
		}
		if($bool){
			if(sizeof(array_keys($children)) == 1){
				foreach($children as $k => $v){
					if($k == 'data' || preg_match('/^[A-Z]/',$k)){
						return !isset($v[0]) ? [$v] : $v;
					}
				}
			}
			return $children;
		}
		return $this->value();
	}

	/**
	 * 匿名タグとしてインスタンス生成
	 */
	public static function anonymous(string $value): self{
		$xml = new self('XML'.uniqid());
		$xml->escape(false);
		$xml->value($value);
		$xml->escape(true);
		return $xml;
	}

	/**
	 * タグの検出（SimpleXML使用）
	 */
	public static function extract(?string $plain=null, ?string $name=null): self{
		if(empty($plain)){
			throw new \ebi\exception\NotFoundException('empty plain');
		}

		if(!empty($name)){
			$names = explode('/',$name,2);
			$name = $names[0];
		}

		$x = self::find_extract_simple($plain, $name);

		if($x !== null){
			if(isset($names[1])){
				try{
					return $x->find_get($names[1]);
				}catch(\ebi\exception\NotFoundException $e){
				}
			}else{
				return $x;
			}
		}
		throw new \ebi\exception\NotFoundException(($name ?? 'tag').' not found');
	}

	/**
	 * SimpleXMLを使用したタグ検出
	 */
	private static function find_extract_simple(string $plain, ?string $name): ?self{
		// タグ名が未指定の場合、最初のタグを検出
		if(empty($name)){
			if(preg_match('/<([\w\:\-]+)[\s>]/s', $plain, $m)){
				$name = $m[1];
			}else{
				return null;
			}
		}

		// タグの開始位置を検出（大文字小文字を区別しない）
		$pattern = '/<'.preg_quote($name, '/').'[\s>\/]/i';
		if(!preg_match($pattern, $plain, $m, PREG_OFFSET_CAPTURE)){
			return null;
		}
		$pos = $m[0][1];

		// タグ部分を抽出
		$tag_plain = self::extract_tag_string($plain, $name, $pos);
		if($tag_plain === null){
			return null;
		}

		// SimpleXMLでパース
		libxml_use_internal_errors(true);
		$sxml = @simplexml_load_string($tag_plain);

		if($sxml === false){
			// SimpleXMLで失敗した場合、CDATAや特殊文字の問題の可能性
			// フォールバックとして正規表現でパース
			return self::find_extract_regex($name, $pos, $tag_plain);
		}

		$x = new self();
		$x->pos = $pos;
		$x->plain = $tag_plain;
		$x->name = $sxml->getName();

		// 属性を設定
		foreach($sxml->attributes() as $attr_name => $attr_value){
			$x->attr($attr_name, (string)$attr_value);
		}

		// 子要素の内容を取得（innerXML）
		$inner = self::get_inner_xml($sxml);
		$x->value = ($inner === '') ? null : $inner;

		libxml_clear_errors();
		return $x;
	}

	/**
	 * タグ文字列を抽出
	 */
	private static function extract_tag_string(string $plain, string $name, int $start_pos): ?string{
		$q_name = preg_quote($name, '/');

		// 自己終了タグをチェック（大文字小文字を区別しない）
		if(preg_match('/<'.$q_name.'(?:\s[^>]*)?\s*\/>/si', $plain, $m, PREG_OFFSET_CAPTURE, $start_pos)){
			if($m[0][1] === $start_pos){
				return $m[0][0];
			}
		}

		// 開始・終了タグのバランスを追跡（大文字小文字を区別しない）
		$depth = 0;
		$offset = $start_pos;
		$open_tag = null;
		$pattern = '/<\/?'.$q_name.'(?:\s[^>]*)?(?:\/)?>/si';

		while(preg_match($pattern, $plain, $m, PREG_OFFSET_CAPTURE, $offset)){
			$tag = $m[0][0];
			$tag_pos = $m[0][1];

			if(substr($tag, -2) === '/>'){
				// 自己終了タグ
				if($depth === 0){
					return substr($plain, $start_pos, $tag_pos + strlen($tag) - $start_pos);
				}
			}else if($tag[1] === '/'){
				// 終了タグ
				$depth--;
				if($depth === 0){
					return substr($plain, $start_pos, $tag_pos + strlen($tag) - $start_pos);
				}
			}else{
				// 開始タグ
				if($depth === 0){
					$open_tag = $tag;
				}
				$depth++;
			}

			$offset = $tag_pos + strlen($tag);
		}

		// 閉じタグが見つからない場合、開始タグを自己終了タグとして返す
		if($open_tag !== null){
			return preg_replace('/\s*>$/', ' />', $open_tag);
		}

		return null;
	}

	/**
	 * SimpleXMLからinnerXMLを取得
	 */
	private static function get_inner_xml(\SimpleXMLElement $sxml): string{
		$dom = dom_import_simplexml($sxml);
		$inner = '';

		foreach($dom->childNodes as $child){
			if($child->nodeType === XML_TEXT_NODE){
				// テキストノードはエンコードせずそのまま取得
				$inner .= $child->nodeValue;
			}else{
				$inner .= $dom->ownerDocument->saveXML($child);
			}
		}

		return $inner;
	}

	/**
	 * 正規表現によるフォールバックパース
	 */
	private static function find_extract_regex(string $name, int $pos, string $tag_plain): ?self{
		$x = new self();
		$x->pos = $pos;
		$x->plain = $tag_plain;
		$x->name = $name;

		$q_name = preg_quote($name, '/');

		// 自己終了タグ（大文字小文字を区別しない）
		if(preg_match('/^<'.$q_name.'(\s[^>]*)?\s*\/>$/si', $tag_plain, $m)){
			$x->value = null;
			if(!empty($m[1])){
				self::parse_attributes($x, $m[1]);
			}
			return $x;
		}

		// 開始・終了タグ（大文字小文字を区別しない）
		if(preg_match('/^<'.$q_name.'(\s[^>]*)?>(.*)(<\/'.$q_name.'\s*>)$/si', $tag_plain, $m)){
			if(!empty($m[1])){
				self::parse_attributes($x, $m[1]);
			}
			$x->value = ($m[2] === '') ? null : $m[2];
			return $x;
		}

		return null;
	}

	/**
	 * 属性文字列をパース
	 */
	private static function parse_attributes(self $x, string $attr_str): void{
		if(preg_match_all('/\s+([\w\-\:]+)\s*=\s*(["\'])([^\\2]*?)\\2/s', $attr_str, $matches, PREG_SET_ORDER)){
			foreach($matches as $m){
				$x->attr($m[1], $m[3]);
			}
		}
		// 値なし属性
		$cleaned = preg_replace('/\s+[\w\-\:]+\s*=\s*["\'][^"\']*["\']/', '', $attr_str);
		if(preg_match_all('/([\w\-]+)/', $cleaned, $matches)){
			foreach($matches[1] as $v){
				$x->attr($v, $v);
			}
		}
	}

	/**
	 * 整形する
	 */
	public static function format(string $src, string $indent_str="\t", int $depth=0): string{
		// 従来の整形処理を使用（DOMDocumentは空要素の扱いが異なるため）
		return self::format_legacy($src, $indent_str, $depth);
	}

	/**
	 * 従来の整形処理（フォールバック）
	 */
	private static function format_legacy(string $src, string $indent_str="\t", int $depth=0): string{
		$rtn = '';
		$i = 0;
		$c = md5(__FILE__);
		$m = [];

		if(preg_match_all('/<([\w_]+?)[^><]*><\/\\1>/', $src,$m)){
			foreach($m[0] as $s){
				$src = str_replace($s,str_replace('><','>'.$c.'<',$s),$src);
			}
		}
		foreach(explode("\n",preg_replace('/>\s*</','>'."\n".'<',$src)) as $line){
			$indent = 0;
			$lc = substr_count($line,'<');

			if($lc == 1){
				if(strpos($line,'<?') === false && strpos($line,'/>') === false){
					if(($p = strpos(trim($line),'</')) !== false){
						if($p !== 0){
							$indent = 2;
						}
						$i--;
					}else if(strpos($line,'<!') === false){
						$indent = 1;
					}
				}
			}else if($lc == 0){
				$indent = 2;
			}
			$rtn .= (($indent != 2 && ($i+$depth) > 0) ? str_repeat($indent_str,$i+$depth) : '').$line."\n";

			if($indent == 1){
				$i++;
			}
		}
		$rtn = str_replace($c,'',$rtn);

		if(preg_match_all('/\s+<!\[CDATA\[(.+)\]\]>\s+/s',$rtn,$m)){
			foreach($m[0] as $s){
				$rtn = str_replace($s,trim($s),$rtn);
			}
		}
		return $rtn;
	}

	/**
	 * $srcから対象のXMLを置換した文字列を返す
	 */
	public static function find_replace(string $src, string $name, callable $func): string{
		if(!is_callable($func)){
			throw new \ebi\exception\InvalidArgumentException('invalid function');
		}
		foreach(self::anonymous($src)->find($name) as $xml){
			$replace = call_user_func_array($func,[$xml]);
			if(!is_null($replace)){
				$src = str_replace($xml->plain(),(($replace instanceof self) ? $replace->get() : $replace),$src);
			}
		}
		return $src;
	}

	/**
	 * $srcから対象のXMLをすべて置換した文字列を返す
	 */
	public static function find_replace_all(string $src, string $name, callable $func): string{
		try{
			if(!is_callable($func)){
				throw new \ebi\exception\InvalidArgumentException('invalid function');
			}
			$i = 0;

			while(true){
				$xml = self::extract($src,$name);
				$replace = call_user_func_array($func,[$xml]);

				if(!is_null($replace)){
					$src = str_replace($xml->plain(),(($replace instanceof self) ? $replace->get() : $replace),$src);
				}
				if($i++ > 100){
					throw new \ebi\exception\RetryLimitOverException('Maximum function nesting level of 100');
				}
			}
		}catch(\ebi\exception\NotFoundException $e){
		}
		return $src;
	}
}
