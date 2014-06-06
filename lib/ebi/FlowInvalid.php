<?php
namespace ebi;

class FlowInvalid implements \Iterator{
	private static $self;
	private $messages = [];
	private $pos = 0;
	private $group = null;
	
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
			if(empty($this->group) || $this->messages[$this->pos]['group'] === $this->group){
				return true;
			}
			$this->pos++;
		}
		return false;
	}
	public function next(){
		$this->pos++;
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
	 * @return Exception[]
	 */
	public static function get($group=null){
		if(!self::has($group)) return [];
		self::$self->group = $group;
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
	public static function has($group=null){
		if(self::$self === null) return false;
		if(empty($group)) return !empty(self::$self->messages);
		foreach(self::$self->messages as $e){
			if($e['group'] === $group) return true;
		}
		return false;
	}
	public function before_template($src){
		try{
			while(true){
				$tag = \ebi\Xml::extract($src,'rt:invalid');
				$param = $tag->in_attr('param');
				$var = $tag->in_attr('var','rtinvalid_var'.uniqid(''));
				if(!isset($param[0]) || $param[0] !== '$') $param = '"'.$param.'"';
				$value = $tag->value();
				$tagtype = $tag->in_attr('tag');
		
				if(empty($value)){
					$varnm = 'rtinvalid_varnm'.uniqid('');
					$value = sprintf('<div class="%s"><ul><rt:loop param="%s" var="%s">'.PHP_EOL
							.'<li>{$%s.getMessage()}</li>'
							.'</rt:loop></ul></div>'
							,$tag->in_attr('class','alert alert-danger'),$var,$varnm,$varnm,((empty($tagtype)) ? '' : '</'.$tagtype.'>'));
				}
				$src = str_replace(
						$tag->plain(),
						sprintf("<?php if(\\ebi\\FlowInvalid::has(%s)){ ?>"
								."<?php \$%s = \\ebi\\FlowInvalid::get(%s); ?>"
								.preg_replace("/<rt\:else[\s]*.*?>/i","<?php }else{ ?>",$value)
								."<?php } ?>"
								,$param
								,$var,$param
						),
						$src);
			}
		}catch(\ebi\exception\NotFoundException $e){
		}
		return $src;
	}
}