<?php
namespace ebi;

class Xml implements \IteratorAggregate{
	private array $attr = [];
	private array $plain_attr = [];
	private ?string $name;
	private ?string $value;
	private bool $close_empty = true;

	private string $plain;
	private int $pos;
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
		$this->esc = (bool)$bool;
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
			$this->value .= $this->get_value(func_get_arg(0));
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
	 * タグの検出
	 */
	public static function extract(?string $plain=null, ?string $name=null): self{
		if(!empty($name)){
			$names = explode('/',$name,2);
			$name = $names[0];
		}
		
		$x = null;
		if(self::find_extract($x,$plain,$name)){
			if(isset($names[1])){
				try{
					return $x->find_get($names[1]);
				}catch(\ebi\exception\NotFoundException $e){
				}
			}else{
				return $x;
			}
		}
		throw new \ebi\exception\NotFoundException($name.' not found');
	}

	
	private static function find_extract(&$x, $plain, $name=null, $v_xml=null): bool{
		$plain = (string)$plain;
		$name = (string)$name;
		$m = [];
		
		if(empty($name) && preg_match("/<([\w\:\-]+)[\s][^>]*?>|<([\w\:\-]+)>/is",$plain,$m)){
			$name = str_replace(["\r\n","\r","\n"],'',(empty($m[1]) ? $m[2] : $m[1]));
		}
		$q_name = preg_quote($name,'/');
		$parse = $matches = [];
		if(!preg_match("/<(".$q_name.")([\s][^>]*?)>|<(".$q_name.")>|<(".$q_name.")\/>/is",$plain,$parse,PREG_OFFSET_CAPTURE)){
			return false;
		}
		$x = new self();
		$x->pos = $parse[0][1];
		$balance = 0;
		$attrs = '';

		if(substr($parse[0][0],-2) == '/>'){
			$x->name = $parse[1][0];
			$x->plain = empty($v_xml) ? $parse[0][0] : preg_replace('/'.preg_quote(substr($v_xml,0,-1).' />','/').'/',$v_xml,$parse[0][0],1);
			$attrs = $parse[2][0];
		}else if(preg_match_all("/<[\/]{0,1}".$q_name."[\s][^>]*[^\/]>|<[\/]{0,1}".$q_name."[\s]*>/is",$plain,$matches,PREG_OFFSET_CAPTURE,$x->pos)){
			foreach($matches[0] as $arg){
				$balance += (($arg[0][1] == '/') ? -1 : 1);
				
				if($balance <= 0 &&
					preg_match("/^(<(".$q_name.")([\s]*[^>]*)>)(.*)(<\/\\2[\s]*>)$/is",
						substr($plain,$x->pos,($arg[1] + strlen($arg[0]) - $x->pos)),
						$m
					)
				){
					$x->plain = $m[0];
					$x->name = $m[2];
					$x->value = ($m[4] === '' || $m[4] === null) ? null : $m[4];
					$attrs = $m[3];
					break;
				}
			}
			if(!isset($x->plain)){
				return self::find_extract($x,preg_replace('/'.preg_quote($matches[0][0][0],'/').'/',substr($matches[0][0][0],0,-1).' />',$plain,1),$name,$matches[0][0][0]);
			}
		}
		if(!isset($x->plain)){
			return false;
		}
		if(!empty($attrs)){
			$attr = [];
			if(preg_match_all("/[\s]+([\w\-\:]+)[\s]*=[\s]*([\"\'])([^\\2]*?)\\2/ms",$attrs,$attr)){
				foreach($attr[0] as $id => $value){
					$x->attr($attr[1][$id],$attr[3][$id]);
					$attrs = str_replace($value,'',$attrs);
				}
			}
			if(preg_match_all("/([\w\-]+)/",$attrs,$attr)){
				foreach($attr[1] as $v) $x->attr($v,$v);
			}
		}
		return true;
	}

	/**
	 * 整形する
	 */
	public static function format(string $src, string $indent_str="\t", int $depth=0): string{
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
					throw new \ebi\exception\RetryLimitOverException('Maximum function nesting level of ’100');
				}
			}
		}catch(\ebi\exception\NotFoundException $e){
		}
		return $src;
	}
}
