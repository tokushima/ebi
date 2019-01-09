<?php
namespace ebi;
/**
 * Flowのエラー時処理
 * @author tokushima
 *
 */
class FlowInvalid implements \Iterator{
	private static $self;
	private $messages = [];
	private $pos = 0;
	private $group = null;
	private $type = null;
	
	public function rewind(){
		$this->pos = 0;
	}
	public function current(){
		return $this->messages[$this->pos]['exception'];
	}
	public function key(){
		return $this->messages[$this->pos]['group'];
	}
	public function valid(){
		while($this->pos < sizeof($this->messages)){
			if((empty($this->group) || $this->messages[$this->pos]['group'] === $this->group) &&
				(empty($this->type) || ($this->messages[$this->pos]['exception'] instanceof $this->type))
			){
				return true;
			}
			$this->pos++;
		}
		return false;
	}
	public function next(){
		$this->pos++;
	}
	private static function type($type){
		if(!empty($type)){
			$type = str_replace('.','\\',$type);
			if($type[0] != '\\'){
				$type = '\\'.$type;
			}
		}
		return $type;
	}
	public static function set(\Exception $exception){
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
	 * 追加されたExceptionからException配列を取得
	 * @param string $group グループ名
	 * @param string $type 例外クラス名
	 * @return Exception[]
	 */
	public static function get($group=null,$type=null){
		if(self::$self === null) return [];
		self::$self->group = $group;
		self::$self->type = self::type($type);
		return self::$self;
	}
	/**
	 * 追加されたExceptionのクリア
	 */
	public static function clear(){
		if(isset(self::$self)){
			self::$self->messages = [];
		}
	}
	/**
	 * Exceptionが追加されているか
	 * @param string $group グループ名
	 * @return boolean
	 */
	public static function has($group=null,$type=null){
		if(self::$self === null) return false;
		self::$self->group = $group;
		self::$self->type = self::type($type);
		
		reset(self::$self);
		return (next(self::$self) !== false);
	}
	public function before_template($src){
		return \ebi\Xml::find_replace_all($src,'rt:invalid',function($xml){
			$param = $xml->in_attr('param');
			$type = $xml->in_attr('type');
			$var = $xml->in_attr('var','rtinvalid_var'.uniqid(''));
			if(!isset($param[0]) || $param[0] !== '$'){
				$param = '"'.$param.'"';
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
					,$param,$type
					,$var,$param,$type
			);
		});
	}
}