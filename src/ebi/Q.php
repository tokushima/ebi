<?php
namespace ebi;
/**
 * Where query
 * @author tokushima
 */
class Q{
	const EQ = 1;
	const NEQ = 2;
	const GT = 3;
	const LT = 4;
	const GTE = 5;
	const LTE = 6;
	const START_WITH = 7;
	const END_WITH = 8;
	const CONTAINS = 9;
	const IN = 10;
	const ORDER_ASC = 11;
	const ORDER_DESC = 12;
	const ORDER_RAND = 13;
	const ORDER = 14;
	const MATCH = 15;

	const OR_BLOCK = 16;
	const AND_BLOCK = 17;
	const DATE_FORMAT = 18;

	const IGNORE = 2;
	const NOT = 4;

	private $arg1;
	private $arg2;
	private $type;
	private $param;
	private $and_block = [];
	private $or_block = [];
	private $paginator;
	private $order_by = [];
	private $date_format = [];

	public function __construct($type=self::AND_BLOCK,$arg1=null,$arg2=null,$param=null){
		if($type === self::AND_BLOCK){
			$this->and_block = $arg1;
		}else if($type === self::OR_BLOCK){
			if(!is_array($arg1) || sizeof($arg1) < 2) throw new \InvalidArgumentException('require multiple blocks');
			foreach($arg1 as $a){
				if(!$a->is_block()) throw new \InvalidArgumentException('require multiple blocks');
			}
			$this->or_block = $arg1;
		}else{
			$this->arg1 = $arg1;
		}
		$this->arg2 = $arg2;
		$this->type = $type;
		
		if($param !== null){
			if(!ctype_digit((string)$param)) throw new \InvalidArgumentException('`'.(string)$param.'` invalid param type');
			$this->param = decbin($param);
		}
	}
	private function ar_value($v){
		return is_array($v) ? $v : (($v === null) ? [] : [$v]);
	}
	public function ar_arg1(){
		if(empty($this->arg1)) return [];
		if(is_string($this->arg1)){
			$result = [];
			foreach(explode(',',$this->arg1) as $arg){
				if(!empty($arg)) $result[] = $arg;
			}
			return $result;
		}else if($this->arg1 instanceof \ebi\Column){
			return [$this->arg1];
		}
		throw new \InvalidArgumentException('invalid arg1');
	}
	public function ar_arg2(){
		return isset($this->arg2) ? $this->ar_value($this->arg2) : [null];
	}
	public function type(){
		return $this->type;
	}
	public function param(){
		return $this->param;
	}
	public function ar_and_block(){
		return $this->ar_value($this->and_block);
	}
	public function ar_or_block(){
		return $this->ar_value($this->or_block);
	}
	public function is_order_by(){
		return !empty($this->order_by);
	}
	public function in_order_by($key){
		return isset($this->order_by[$key]) ? $this->order_by[$key] : null;
	}
	public function ar_order_by(){
		return $this->ar_value($this->order_by);
	}
	public function paginator(){
		return $this->paginator;
	}
	/**
	 * ソート順がランダムか
	 * @return boolean
	 */
	public function is_order_by_rand(){
		if(empty($this->order_by)) return false;
		foreach($this->order_by as $q){
			if($q->type() == self::ORDER_RAND) return true;
		}
		return false;
	}
	/**
	 * クエリを追加する
	 * @throws \BadMethodCallException
	 */
	public function add(){
		$args = func_get_args();
		foreach($args as $arg){
			if(!empty($arg)){
				if($arg instanceof \ebi\Q){
					if($arg->type() == self::ORDER_ASC || $arg->type() == self::ORDER_DESC || $arg->type() == self::ORDER_RAND){
						$this->order_by[] = $arg;
					}else if($arg->type() == self::ORDER){
						foreach($arg->ar_arg1() as $column){
							if($column[0] === '-'){
								$this->add(new self(self::ORDER_DESC,substr($column,1)));
							}else{
								$this->add(new self(self::ORDER_ASC,$column));
							}
						}
					}else if($arg->type() == self::DATE_FORMAT){
						$this->date_format[$arg->arg1] = $arg->arg2;
					}else if($arg->type() == self::AND_BLOCK){
						if(!$arg->none()){
							call_user_func_array([$this,'add'],$arg->ar_and_block());
							$this->or_block = array_merge($this->or_block,$arg->ar_or_block());
						}
					}else if($arg->type() == self::OR_BLOCK){
						if(!$arg->none()){
							$this->or_block = array_merge($this->or_block,$arg->ar_or_block());
						}
					}else{
						$this->and_block[] = $arg;
					}	
				}else if($arg instanceof \ebi\Paginator){
					$this->paginator = $arg;
				}else{
					throw new \BadMethodCallException('`'.(string)$arg.'` not supported');
				}
			}
		}
		return $this;
	}
	/**
	 * 条件が存在しない
	 * @return boolean
	 */
	public function none(){
		return (empty($this->and_block) && empty($this->or_block));
	}
	/**
	 * 条件ブロックか
	 * @return boolean
	 */
	public function is_block(){
		return ($this->type == self::AND_BLOCK || $this->type == self::OR_BLOCK);
	}
	/**
	 * 大文字小文字を区別しない
	 * @return boolean
	 */
	public function ignore_case(){
		return (strlen($this->param) > 1 && substr($this->param,-2,1) === '1');
	}
	/**
	 * 否定式である
	 * @return boolean
	 */
	public function not(){
		return (strlen($this->param) > 2 && substr($this->param,-3,1) === '1');
	}
	/**
	 * column_str == value
	 * @param string $column_str
	 * @param string $value
	 * @param integer $param
	 */
	public static function eq($column_str,$value,$param=null){
		return new self(self::EQ,$column_str,$value,$param);
	}
	/**
	 * column_str != value
	 * @param string $column_str
	 * @param string $value
	 * @param integer $param
	 */
	public static function neq($column_str,$value,$param=null){
		return new self(self::NEQ,$column_str,$value,$param);
	}
	/**
	 * column_str > value
	 * @param string $column_str
	 * @param string $value
	 * @param integer $param
	 */
	public static function gt($column_str,$value,$param=null){
		return new self(self::GT,$column_str,$value,$param);
	}
	/**
	 * column_str < value
	 * @param string $column_str
	 * @param string $value
	 * @param integer $param
	 */
	public static function lt($column_str,$value,$param=null){
		return new self(self::LT,$column_str,$value,$param);
	}
	/**
	 * column_str >= value
	 * @param string $column_str
	 * @param string $value
	 * @param integer $param
	 */
	public static function gte($column_str,$value,$param=null){
		return new self(self::GTE,$column_str,$value,$param);
	}
	/**
	 * column_str <= value
	 * @param string $column_str
	 * @param string $value
	 * @param integer $param
	 */
	public static function lte($column_str,$value,$param=null){
		return new self(self::LTE,$column_str,$value,$param);
	}
	/**
	 * 前方一致
	 * @param string $column_str
	 * @param string $words
	 * @param integer $param
	 */
	public static function startswith($column_str,$words,$param=null){
		try{
			return new self(self::START_WITH,$column_str,self::words_array($words),$param);
		}catch(\InvalidArgumentException $e){
			return new self();
		}
	}
	/**
	 * 後方一致
	 * @param string $column_str
	 * @param string $words
	 * @param integer $param
	 */
	public static function endswith($column_str,$words,$param=null){
		try{
			return new self(self::END_WITH,$column_str,self::words_array($words),$param);
		}catch(\InvalidArgumentException $e){
			return new self();
		}
	}
	/**
	 * 部分一致
	 * @param string $column_str
	 * @param string $words
	 * @param integer $param
	 */
	public static function contains($column_str,$words,$param=null){
		try{
			return new self(self::CONTAINS,$column_str,self::words_array($words),$param);
		}catch(\InvalidArgumentException $e){
			return new self();
		}
	}
	/**
	 * in
	 * @param string $column_str 指定のプロパティ名
	 * @param string $words 絞り込み文字列
	 * @param integer $param 
	 */
	public static function in($column_str,$words,$param=null){
		try{
			return new self(self::IN,$column_str,($words instanceof \ebi\Daq) ? $words : [self::words_array($words)],$param);
		}catch(\InvalidArgumentException $e){
			return new self();
		}
	}
	private static function words_array($words){
		if($words === '' || $words === null) throw new \InvalidArgumentException();
		if(is_array($words)){
			$result = [];
			foreach($words as $w){
				$w = (string)$w;
				if($w !== '') $result[] = $w;
			}
			if(empty($result)) throw new \InvalidArgumentException();
			return $result;
		}
		return [$words];
	}
	/**
	 * ソート順を指定する
	 * @param string $column_str 指定のプロパティ名
	 */
	public static function order($column_str){
		return new self(self::ORDER,$column_str);
	}
	/**
	 * ソート順をランダムにする
	 */
	public static function random_order(){
		return new self(self::ORDER_RAND);
	}
	/**
	 * 指定の文字列と前回指定の文字列を比較しソート順を指定する
	 * @param string $column_str 指定のプロパティ名
	 * @param string $pre_column_str 前回指定したプロパティ名
	 */
	public static function select_order(&$column_str,$pre_column_str){
		if($column_str == $pre_column_str){
			$column_str = (substr($column_str,0,1) == '-') ? substr($column_str,1) : '-'.$column_str;
		}
		return new self(self::ORDER,$column_str);
	}
	/**
	 * 検索文字列による検索条件
	 * @param string $dict 検索文字列
	 * @param integer $param
	 */
	public static function match($dict,$param=null){
		if(!($param === null || $param === self::IGNORE)) throw new \InvalidArgumentException('invalid param');
		return new self(self::MATCH,str_replace(' ',',',trim($dict)),null,$param);
	}
	/**
	 * OR条件ブロック
	 */
	public static function ob(){
		$args = func_get_args();
		return new self(self::OR_BLOCK,$args);
	}
	/**
	 * 条件ブロック
	 */
	public static function b(){
		$args = func_get_args();
		return new self(self::AND_BLOCK,$args);
	}
	
	
	public static function date_format($column_str,$require){
		return new self(self::DATE_FORMAT,$column_str,$require);
	}
	public function ar_date_format(){
		return $this->ar_value($this->date_format);
	}
}
