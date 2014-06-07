<?php
namespace ebi;
/**
 * XMLを処理する
 * @author tokushima
 */
class Xml implements \IteratorAggregate{
	private $attr = array();
	private $plain_attr = array();
	private $name;
	private $value;
	private $close_empty = true;

	private $plain;
	private $pos;
	private $esc = true;

	public function __construct($name=null,$value=null){
		if($value === null && is_object($name)){
			$n = explode('\\',get_class($name));
			$this->name = array_pop($n);
			$this->value($name);
		}else{
			$this->name = trim($name);
			$this->value($value);
		}
	}
	/**
	 * (non-PHPdoc)
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator(){
		return new \ArrayIterator($this->attr);
	}
	/**
	 * 値が無い場合は閉じを省略する
	 * @param boolean
	 * @return boolean
	 */
	public function close_empty(){
		if(func_num_args() > 0) $this->close_empty = (boolean)func_get_arg(0);
		return $this->close_empty;
	}
	/**
	 * エスケープするか
	 * @param boolean $bool
	 */
	public function escape($bool){
		$this->esc = (boolean)$bool;
		return $this;
	}
	/**
	 * setできた文字列
	 * @return string
	 */
	public function plain(){
		return $this->plain;
	}
	/**
	 * 子要素検索時のカーソル
	 * @return integer
	 */
	public function cur(){
		return $this->pos;
	}
	/**
	 * 要素名
	 * @return string
	 */
	public function name($name=null){
		if(isset($name)) $this->name = $name;
		return $this->name;
	}
	private function get_value($v){
		if($v instanceof self){
			$v = $v->get();
		}else if(is_bool($v)){
			$v = ($v) ? 'true' : 'false';
		}else if($v === ''){
			$v = null;
		}else if(is_array($v) || is_object($v)){
			$r = '';
			foreach($v as $k => $c){
				if(is_numeric($k) && is_object($c)){
					$e = explode('\\',get_class($c));
					$k = array_pop($e);
				}
				if(is_numeric($k)) $k = 'data';
				$x = new self($k,$c);
				$x->escape($this->esc);
				$r .= $x->get();
			}
			$v = $r;
		}else if($this->esc && strpos($v,'<![CDATA[') === false && preg_match("/&|<|>|\&[^#\da-zA-Z]/",$v)){
			$v = '<![CDATA['.$v.']]>';
		}
		return $v;
	}
	/**
	 * 値を設定、取得する
	 * @param mixed
	 * @param boolean
	 * @return string
	 */
	public function value(){
		if(func_num_args() > 0) $this->value = $this->get_value(func_get_arg(0));
		if(strpos($this->value,'<![CDATA[') === 0) return substr($this->value,9,-3);
		return $this->value;
	}
	/**
	 * 値を追加する
	 * ２つ目のパラメータがあるとアトリビュートの追加となる
	 * @param mixed $arg
	 */
	public function add($arg){
		if(func_num_args() == 2){
			$this->attr(func_get_arg(0),func_get_arg(1));
		}else{
			$this->value .= $this->get_value(func_get_arg(0));
		}
		return $this;
	}
	/**
	 * アトリビュートを取得する
	 * @param string $n 取得するアトリビュート名
	 * @param string $d アトリビュートが存在しない場合の代替値
	 * @return string
	 */
	public function in_attr($n,$d=null){
		return isset($this->attr[strtolower($n)]) ? ($this->esc ? htmlentities($this->attr[strtolower($n)],ENT_QUOTES,'UTF-8') : $this->attr[strtolower($n)]) : (isset($d) ? (string)$d : null);
	}
	/**
	 * アトリビュートから削除する
	 * パラメータが一つも無ければ全件削除
	 */
	public function rm_attr(){
		if(func_num_args() === 0){
			$this->attr = array();
		}else{
			foreach(func_get_args() as $n) unset($this->attr[$n]);
		}
	}
	/**
	 * アトリビュートがあるか
	 * @param string $name
	 * @return boolean
	 */
	public function is_attr($name){
		return array_key_exists($name,$this->attr);
	}
	/**
	 * アトリビュートを設定
	 * @return self $this
	 */
	public function attr($key,$value){
		$this->attr[strtolower($key)] = is_bool($value) ? (($value) ? 'true' : 'false') : $value;
		return $this;
	}
	/**
	 * 値の無いアトリビュートを設定
	 * @param string $v
	 */
	public function plain_attr($v){
		$this->plain_attr[] = $v;
	}
	/**
	 * XML文字列を返す
	 */
	public function get($encoding=null){
		if($this->name === null) throw new \LogicException('undef name');
		$attr = '';
		$value = ($this->value === null || $this->value === '') ? null : (string)$this->value;
		foreach(array_keys($this->attr) as $k){
			$attr .= ' '.$k.'="'.$this->in_attr($k).'"';
		}
		return ((empty($encoding)) ? '' : '<?xml version="1.0" encoding="'.$encoding.'" ?'.'>'.PHP_EOL)
				.('<'.$this->name.$attr.(implode(' ',$this->plain_attr)).(($this->close_empty && !isset($value)) ? ' /' : '').'>')
				.$this->value
				.((!$this->close_empty || isset($value)) ? sprintf('</%s>',$this->name) : '');
	}
	public function __toString(){
		return $this->get();
	}
	/**
	 * 検索する
	 * @param string $path
	 * @param integer $offset
	 * @param integer $length
	 * @return \ebi\XmlIterator
	 */
	public function find($path,$offset=0,$length=0){
		if(is_string($path) && strpos($path,'/') !== false){
			list($name,$path) = explode('/',$path,2);
			foreach(new \ebi\XmlIterator($name,$this->value(),0,0) as $t){
				try{
					$it = $t->find($path,$offset,$length);
					if($it->valid()){
						reset($it);
						return $it;
					}
				}catch(\ebi\exception\NotFoundException $e){}
			}
			throw new \ebi\exception\NotFoundException();
		}
		return new \ebi\XmlIterator($path,$this->value(),$offset,$length);
	}
	/**
	 * １件取得する
	 * @param string $path
	 * @param integer $offset
	 * @throws \ebi\exception\NotFoundException
	 * @return $this
	 */
	public function find_get($path,$offset=0){
		foreach($this->find($path,$offset,1) as $x){
			return $x;
		}
		throw new \ebi\exception\NotFoundException($path.' not found');
	}
	/**
	 * 置換して新規のインスタンスを返す
	 * @param string $path
	 * @param string $value
	 * @return self
	 */
	public function replace($path,$value){
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
	 * 匿名タグとしてインスタンス生成
	 * @param string $value
	 * @return self
	 */
	public static function anonymous($value){
		$xml = new self('XML'.uniqid());
		$xml->escape(false);
		$xml->value($value);
		$xml->escape(true);
		return $xml;
	}
	/**
	 * タグの検出
	 * @param string $plain
	 * @param string $name
	 * @throws \ebi\exception\NotFoundException
	 * @return self
	 */
	public static function extract($plain,$name=null){
		if(!(!empty($name) && strpos($plain,$name) === false) && self::find_extract($x,$plain,$name)){
			return $x;
		}
		throw new \ebi\exception\NotFoundException($name.' not found');
	}
	private static function find_extract(&$x,$plain,$name=null,$vtag=null){
		$plain = (string)$plain;
		$name = (string)$name;
		if(empty($name) && preg_match("/<([\w\:\-]+)[\s][^>]*?>|<([\w\:\-]+)>/is",$plain,$m)){
			$name = str_replace(array("\r\n","\r","\n"),'',(empty($m[1]) ? $m[2] : $m[1]));
		}
		$qname = preg_quote($name,'/');
		if(!preg_match("/<(".$qname.")([\s][^>]*?)>|<(".$qname.")>/is",$plain,$parse,PREG_OFFSET_CAPTURE)) return false;
		$x = new self();
		$x->pos = $parse[0][1];
		$balance = 0;
		$attrs = '';

		if(substr($parse[0][0],-2) == '/>'){
			$x->name = $parse[1][0];
			$x->plain = empty($vtag) ? $parse[0][0] : preg_replace('/'.preg_quote(substr($vtag,0,-1).' />','/').'/',$vtag,$parse[0][0],1);
			$attrs = $parse[2][0];
		}else if(preg_match_all("/<[\/]{0,1}".$qname."[\s][^>]*[^\/]>|<[\/]{0,1}".$qname."[\s]*>/is",$plain,$list,PREG_OFFSET_CAPTURE,$x->pos)){
			foreach($list[0] as $arg){
				if(($balance += (($arg[0][1] == '/') ? -1 : 1)) <= 0 &&
						preg_match("/^(<(".$qname.")([\s]*[^>]*)>)(.*)(<\/\\2[\s]*>)$/is",
							substr($plain,$x->pos,($arg[1] + strlen($arg[0]) - $x->pos)),
							$match
						)
				){
					$x->plain = $match[0];
					$x->name = $match[2];
					$x->value = ($match[4] === '' || $match[4] === null) ? null : $match[4];
					$attrs = $match[3];
					break;
				}
			}
			if(!isset($x->plain)){
				return self::find_extract($x,preg_replace('/'.preg_quote($list[0][0][0],'/').'/',substr($list[0][0][0],0,-1).' />',$plain,1),$name,$list[0][0][0]);
			}
		}
		if(!isset($x->plain)) return false;
		if(!empty($attrs)){
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
}
