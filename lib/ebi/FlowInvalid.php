<?php
namespace ebi;

class FlowInvalid implements \Iterator{
	private static self $self;
	private array $messages = [];
	private int $pos = 0;
	private ?string $group = null;
	private ?string $type = null;
	
	public function rewind(): void{
		$this->pos = 0;
	}

	#[\ReturnTypeWillChange]
	public function current(){
		return $this->messages[$this->pos]['exception'];
	}

	#[\ReturnTypeWillChange]
	public function key(){
		return $this->messages[$this->pos]['group'];
	}
	public function valid(): bool{
		while($this->pos < sizeof($this->messages)){
			if((empty($this->group) || $this->messages[$this->pos]['group'] === $this->group) &&
				(empty($this->type) || preg_match('/\\\\'.preg_quote($this->type).'$/','\\'.get_class($this->messages[$this->pos]['exception'])))
			){
				return true;
			}
			$this->pos++;
		}
		return false;
	}
	public function next(): void{
		$this->pos++;
	}
	
	/**
	 * Exceptionをセットする
	 */
	public static function set(\Exception $exception): void{
		self::$self = new self();
		if($exception instanceof \ebi\Exceptions){
			foreach($exception as $group => $e){
				self::$self->messages[] = ['exception'=>$e,'group'=>$group];
			}
		}else{
			self::$self->messages[] = ['exception'=>$exception,'group'=>''];
		}
	}
	
	/**
	 * セットされたExceptionからException配列を取得
	 */
	public static function get(?string $group=null, ?string $type=null): self{
		if(self::$self === null) return [];
		self::$self->group = $group;
		self::$self->type = $type;
		return self::$self;
	}
	
	/**
	 * セットされたExceptionのクリア
	 */
	public static function clear(): void{
		if(isset(self::$self)){
			self::$self->messages = [];
		}
	}
	
	/**
	 * Exceptionが追加されているか
	 */
	public static function has(?string $group=null, ?string $type=null): bool{
		if(self::$self === null){
			return false;
		}
		self::$self->group = $group;
		self::$self->type = $type;
		
		self::$self->rewind();
		return self::$self->valid();
	}
}