<?php
namespace ebi;

class FlowInvalid implements \Iterator{
	private static $self;
	private $messages = [];
	private $pos = 0;
	private $group = null;
	private $type = null;
	
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
	
	/**
	 * Template plugin
	 * 
	 * ```
	 * <rt:invalid type="Exception" group="email" var="exceptions">
	 *  <rt:loop param="{$exceptions}" var="e">{$e.getMessage()}</rt:loop>
	 * </rt:invalid>
	 * ```
	 */
	public function before_template(string $src): string{
		return \ebi\Xml::find_replace_all($src,'rt:invalid',function($xml){
			$group = $xml->in_attr('group');
			$type = $xml->in_attr('type');
			$var = $xml->in_attr('var','rtinvalid_var'.uniqid(''));
			if(!isset($group[0]) || $group[0] !== '$'){
				$group = '"'.$group.'"';
			}
			if(!isset($type[0]) || $type[0] !== '$'){
				$type = '"'.$type.'"';
			}
			$value = $xml->value();
				
			if(empty($value)){
				$varnm = 'rtinvalid_varnm'.uniqid('');
				$value = sprintf('<div class="%s"><ul><rt:loop param="%s" var="%s">'.PHP_EOL
					.'<li><rt:if param="{$t.has($%s.getMessage())}">{$%s.getMessage()}<rt:else />{$t.get_class($%s)}</rt:if></li>'
					.'</rt:loop></ul></div>'
					,$xml->in_attr('class','alert alert-danger'),$var,$varnm,
					$varnm,$varnm,$varnm
				);
			}
			return sprintf("<?php if(\\ebi\\FlowInvalid::has(%s,%s)){ ?>"
				."<?php \$%s = \\ebi\\FlowInvalid::get(%s,%s); ?>"
				.preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$value)
				."<?php } ?>"
				,$group,$type
				,$var,$group,$type
			);
		});
	}
}