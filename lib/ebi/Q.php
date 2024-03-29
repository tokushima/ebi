<?php
namespace ebi;

class Q{
	public const EQ = 1;
	public const NEQ = 2;
	public const GT = 3;
	public const LT = 4;
	public const GTE = 5;
	public const LTE = 6;
	public const START_WITH = 7;
	public const END_WITH = 8;
	public const CONTAINS = 9;
	public const IN = 10;
	public const ORDER_ASC = 11;
	private const ORDER_DESC = 12;
	private const ORDER_RAND = 13;
	public const ORDER = 14;
	public const MATCH = 15;
	
	private const OR_BLOCK = 16;
	private const AND_BLOCK = 17;
	private const DATE_FORMAT = 18;
	private const FOR_UPDATE = 19;
	
	public const IGNORE = 2;
	public const NOT = 4;
	
	private $arg1;
	private $arg2;
	private int $type;
	private $param;
	private ?array $and_block = [];
	private ?array $or_block = [];
	private ?\ebi\Paginator $paginator = null;
	private array $order_by = [];
	private array $date_format = [];
	private bool $for_update = false;

	/**
	 * @param mixed $arg1 
	 * @param mixed $arg2 
	 */
	public function __construct(int $type=self::AND_BLOCK, $arg1=null, $arg2=null, ?string $param=null){
		if($type === self::AND_BLOCK){
			$this->and_block = $arg1;
		}else if($type === self::OR_BLOCK){
			if(!is_array($arg1) || sizeof($arg1) < 2){
				throw new \ebi\exception\InvalidArgumentException('require multiple blocks');
			}
			foreach($arg1 as $k => $a){
				if(!$a->is_block()){
					$arg1[$k] = self::b($a);
				}
			}
			$this->or_block[] = $arg1;
		}else{
			$this->arg1 = $arg1;
		}
		$this->arg2 = $arg2;
		$this->type = $type;
		
		if($param !== null){
			if(!ctype_digit((string)$param)){
				throw new \ebi\exception\InvalidArgumentException('`'.(string)$param.'` invalid param type');
			}
			$this->param = decbin($param);
		}
	}

	/**
	 * @param mixed $v
	 */
	private function ar_value($v): array{
		return is_array($v) ? $v : (($v === null) ? [] : [$v]);
	}
	public function ar_arg1(): array{
		if(empty($this->arg1)){
			return [];
		}
		if(is_string($this->arg1)){
			$result = [];
			foreach(explode(',',$this->arg1) as $arg){
				if(!empty($arg)){
					$result[] = $arg;
				}
			}
			return $result;
		}else if($this->arg1 instanceof \ebi\Column){
			return [$this->arg1];
		}
		throw new \ebi\exception\InvalidArgumentException('invalid arg1');
	}
	public function ar_arg2(): array{
		return isset($this->arg2) ? $this->ar_value($this->arg2) : [null];
	}
	public function type(): int{
		return $this->type;
	}
	public function param(): ?string{
		return $this->param;
	}
	public function ar_and_block(): array{
		return $this->ar_value($this->and_block);
	}
	
	public function ar_or_block(): array{
		return $this->ar_value($this->or_block);
	}
	public function is_order_by(): bool{
		return !empty($this->order_by);
	}
	public function in_order_by(int $index): ?self{
		return $this->order_by[$index] ?? null;
	}
	public function ar_order_by(): array{
		return $this->ar_value($this->order_by);
	}
	public function paginator(): ?\ebi\Paginator{
		return $this->paginator;
	}
	public function ar_date_format(): array{
		return $this->ar_value($this->date_format);
	}
	public function is_for_update(): bool{
		return $this->for_update;
	}
	/**
	 * ソート順がランダムか
	 */
	public function is_order_by_rand(): bool{
		if(empty($this->order_by)){
			return false;
		}
		foreach($this->order_by as $q){
			if($q->type() == self::ORDER_RAND){
				return true;
			}
		}
		return false;
	}
	/**
	 * クエリを追加する
	 */
	public function add(...$args): self{
		foreach($args as $arg){
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
				}else if($arg->type() == self::FOR_UPDATE){
					$this->for_update = true;
				}else if($arg->type() == self::AND_BLOCK){
					call_user_func_array([$this,'add'],$arg->ar_and_block());
					$this->or_block = array_merge($this->or_block,$arg->ar_or_block());
				}else if($arg->type() == self::OR_BLOCK){
					$this->or_block = array_merge($this->or_block,$arg->ar_or_block());
				}else{
					$this->and_block[] = $arg;
				}
			}else if($arg instanceof \ebi\Paginator){
				$this->paginator = $arg;
			}else if($arg instanceof \ebi\Request){
				if($arg->is_vars('query')){
					$this->add(self::match((string)$arg->in_vars('query')));
				}
			}else{
				throw new \ebi\exception\BadMethodCallException('`'.(string)$arg.'` not supported');
			}
		}
		return $this;
	}
	/**
	 * 条件が存在しない
	 */
	public function none(): bool{
		return (empty($this->and_block) && empty($this->or_block));
	}
	/**
	 * 条件ブロックか
	 */
	public function is_block(): bool{
		return ($this->type == self::AND_BLOCK || $this->type == self::OR_BLOCK);
	}
	/**
	 * 大文字小文字を区別しない
	 * decbin(IGNORE)
	 */
	public function ignore_case(): bool{
		return (!empty($this->param) && strlen($this->param) > 1 && substr($this->param,-2,1) === '1');
	}
	/**
	 * 否定式である
	 * decbin(NOT)
	 */
	public function not(): bool{
		return (!empty($this->param) && strlen($this->param) > 2 && substr($this->param,-3,1) === '1');
	}
	/**
	 * column_str == value
	 * @param mixed $value
	 */
	public static function eq(string $column_str, $value, ?string $param=null): self{
		return new self(self::EQ, $column_str, $value, $param);
	}
	/**
	 * column_str != value
	 * @param mixed $value
	 */
	public static function neq(string $column_str, $value, ?string $param=null): self{
		return new self(self::NEQ,$column_str, $value, $param);
	}
	/**
	 * column_str > value
	 * @param mixed $value
	 */
	public static function gt(string $column_str, $value, ?string $param=null): self{
		return new self(self::GT, $column_str, $value, $param);
	}
	/**
	 * column_str < value
	 * @param mixed $value
	 */
	public static function lt(string $column_str, $value, ?string $param=null): self{
		return new self(self::LT, $column_str, $value, $param);
	}
	/**
	 * column_str >= value
	 * @param mixed $value
	 */
	public static function gte(string $column_str, $value, ?string $param=null): self{
		return new self(self::GTE, $column_str, $value, $param);
	}
	/**
	 * column_str <= value
	 * @param mixed $value
	 */
	public static function lte(string $column_str, $value, ?string $param=null): self{
		return new self(self::LTE, $column_str, $value, $param);
	}
	/**
	 * 前方一致
	 * @param mixed $words string|array
	 */
	public static function startswith(string $column_str, $words, ?string $param=null): self{
		try{
			return new self(self::START_WITH, $column_str, self::words_array($words), $param);
		}catch(\ebi\exception\EmptyException $e){
			return new self();
		}
	}
	/**
	 * 後方一致
	 * @param mixed $words string|array
	 */
	public static function endswith(string $column_str, $words, ?string $param=null): self{
		try{
			return new self(self::END_WITH, $column_str, self::words_array($words), $param);
		}catch(\ebi\exception\EmptyException $e){
			return new self();
		}
	}
	/**
	 * 部分一致
	 * @param mixed $words string|array
	 */
	public static function contains(string $column_str, $words, ?string $param=null): self{
		try{
			return new self(self::CONTAINS, $column_str, self::words_array($words), $param);
		}catch(\ebi\exception\EmptyException $e){
			return new self();
		}
	}
	/**
	 * in
	 * @param mixed $words array|\ebi\Daq
	 */
	public static function in(string $column_str, $words, ?string $param=null): self{
		try{
			return new self(self::IN,$column_str,($words instanceof \ebi\Daq) ? $words : [self::words_array($words)],$param);
		}catch(\ebi\exception\EmptyException $e){
			return new self();
		}
	}
	private static function words_array($words){
		if($words === '' || $words === null){
			throw new \ebi\exception\EmptyException();
		}
		if(is_array($words)){
			$result = [];
			
			if(sizeof($words) == 2 && 
				isset($words[0]) && (is_array($words[0]) || ($words[0] instanceof \Traversable)) && 
				isset($words[1]) && is_string($words[1])
			){
				foreach($words[0] as $o){
					$v = $o->{$words[1]}();
					
					if($v !== ''){
						$result[] = $v;
					}
				}
			}else{
				foreach($words as $w){
					$w = (string)$w;
					
					if($w !== ''){
						$result[] = $w;
					}
				}
			}
			if(empty($result)){
				throw new \ebi\exception\EmptyException();
			}
			return $result;
		}
		return [$words];
	}
	/**
	 * ソート順を指定する
	 */
	public static function order(string $column_str): self{
		return new self(self::ORDER, $column_str);
	}
	/**
	 * ソート順をランダムにする
	 */
	public static function random_order(): self{
		return new self(self::ORDER_RAND);
	}
	/**
	 * FOR UPDATE
	 */
	public static function for_update(): self{
		return new self(self::FOR_UPDATE);
	}

	/**
	 * 検索文字列による検索条件
	 * @param mixed $columns array|string
	 */
	public static function match(?string $val, $columns=[]): self{
		if(!empty($columns) && !is_array($columns)){
			$columns = explode(',',$columns);
		}
		$values = trim(str_replace(['　',' '], ' ', (string)$val));
		
		if(!empty($values)){
			$values = array_unique(explode(' ', $values));
		}
		if(empty($values)){
			return new self();
		}
		if(sizeof($columns) == 1){
			return self::contains(implode('',$columns), $values);
		}
		return new self(self::MATCH,implode(',',$values), $columns);
	}
	/**
	 * OR条件ブロック
	 */
	public static function ob(...$args): self{
		return new self(self::OR_BLOCK, $args);
	}
	/**
	 * 条件ブロック
	 */
	public static function b(...$args): self{
		return new self(self::AND_BLOCK, $args);
	}
	
	/**
	 * 日付型で有効とするフォーマットを指定する
	 * require_format: YmdHis
	 * 「2000-01-01 00:00:00」にrequire_formatで指定した値を埋める
	 */	
	public static function date_format(string $column_str, string $require_format): self{
		return new self(self::DATE_FORMAT, $column_str, $require_format);
	}
	/**
	 * 範囲
	 * @param mixed $from 
	 * @param mixed $to
	 */
	public static function between(string $column_str,$from,$to): self{
		$m = [];
		if(preg_match('/^\d{4}([\/\-\.])\d{2}/',$from,$m)){
			$exp = explode(' ',$from,2);

			$xp = explode($m[1],$exp[0],3);
			$d = $xp[0].'/'.(isset($xp[1]) ? $xp[1] : '01').'/'.(isset($xp[2]) ? $xp[2] : '01');
			
			if(!isset($exp[1])){
				$from = $d.' 00:00:00';
			}else{
				$xp = explode(':',$exp[1],3);
				$from = $d.' '.$xp[0].':'.(isset($xp[1]) ? $xp[1] : '00').':'.(isset($xp[2]) ? $xp[2] : '00');
			}
		}
		if(preg_match('/^\d{4}([\/\-\.])\d{2}/',$to,$m)){
			$exp = explode(' ',$to,2);
		
			$xp = explode($m[1],$exp[0],3);
			$d = $xp[0].'/'.(isset($xp[1]) ? $xp[1] : '12').'/';
			$d = isset($xp[2]) ? 
					($d.$xp[2]) : 
					date('Y-m-d',strtotime('last day of '.$d.'01'));
				
			if(!isset($exp[1])){
				$to = $d.' 23:59:59';
			}else{
				$xp = explode(':',$exp[1],3);
				$to = $d.' '.$xp[0].':'.(isset($xp[1]) ? $xp[1] : '59').':'.(isset($xp[2]) ? $xp[2] : '59');
			}
		}
		
		$q = new self();
		$q->add(self::gte($column_str, $from));
		$q->add(self::lte($column_str, $to));
		return $q;
	}
}
