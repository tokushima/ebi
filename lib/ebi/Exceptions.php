<?php
namespace ebi;
/**
 * 例外の集合
 * @author tokushima
 */
class Exceptions extends \ebi\Exception implements \Iterator{
	private static $self;
	private $messages = [];
	private $pos = 0;
	private $group = null;
	
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
			if(empty($this->group) || $this->messages[$this->pos]['group'] == $this->group){
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
	 * Exceptionを追加する
	 * @param Exception $exception 例外
	 * @param string $group グループ名
	 */
	public static function add(\Exception $exception,$group=''){
		if(!isset(self::$self)){
			self::$self = new self();
		}
		if($exception instanceof self){
			foreach($exception as $g => $e){
				self::$self->messages[] = ['exception'=>$e,'group'=>$g];
				self::$self->message .= $exception->getMessage().PHP_EOL;
			}
		}else{
			self::$self->messages[] = ['exception'=>$exception,'group'=>$group];
			self::$self->message .= $exception->getMessage().PHP_EOL;
		}
		return self::$self;
	}
	/**
	 * Exceptionが追加されていればthrowする
	 */
	public static function throw_over(){
		if(isset(self::$self) && !empty(self::$self->messages)){
			$self = self::$self;
			self::$self = null;
			throw $self;
		}
	}
	/**
	 * Exceptionが追加されているか
	 * @param string $group
	 * @return bool
	 */
	public static function has($group=null){
		if(!isset(self::$self)){
			return false;
		}
		if(empty($group)){
			return !empty(self::$self->messages);
		}
		foreach(self::$self->messages as $e){
			if($e['group'] == $group){
				return true;
			}
		}
		return false;
	}
	public static function clear(){
		self::$self = null;
	}
}