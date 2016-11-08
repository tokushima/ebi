<?php
namespace ebi;
/**
 * XMLクラスのイテレータ
 * @author tokushima
 *
 */
class XmlIterator implements \Iterator{
	private $name = null;
	private $plain = null;
	private $tag = null;
	private $offset = 0;
	private $length = 0;
	private $count = 0;

	public function __construct($tag_name,$value,$offset,$length){
		$this->name = $tag_name;
		$this->plain = $value;
		$this->offset = $offset;
		$this->length = $length;
		$this->count = 0;
	}
	public function key(){
		$this->tag->name();
	}
	public function current(){
		$this->plain = substr($this->plain,0,$this->tag->cur()).substr($this->plain,$this->tag->cur() + strlen($this->tag->plain()));
		$this->count++;
		return $this->tag;
	}
	public function valid(){
		if($this->length > 0 && ($this->offset + $this->length) <= $this->count){
			return false;
		}
		if(is_string($this->name) && strpos($this->name,'|') !== false){
			$this->name = explode('|',$this->name);
		}
		if(is_array($this->name)){
			$tags = [];
			foreach($this->name as $name){
				try{
					$get_tag = \ebi\Xml::extract($this->plain,$name);
					$tags[$get_tag->cur()] = $get_tag;
				}catch(\ebi\exception\NotFoundException $e){
				}
			}
			if(empty($tags)) return false;
			ksort($tags,SORT_NUMERIC);
			foreach($tags as $this->tag) return true;
		}
		try{
			$this->tag = \ebi\Xml::extract($this->plain,$this->name);
			return true;
		}catch(\ebi\exception\NotFoundException $e){
		}
		return false;
	}
	public function next(){
	}
	public function rewind(){
		for($i=0;$i<$this->offset;$i++){
			if($this->valid()){
				$this->current();
			}
		}
	}
}