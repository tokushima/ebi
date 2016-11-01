<?php

namespace ebi\man;
/**
 * @var string $name
 * @var text $document
 * @var \ebi\man\DocParam[] $params
 * @var \ebi\man\DocParam $return
 * @author tokushima
 *
 */
class DocInfo extends \ebi\Object{
	protected $name;
	protected $document;
	protected $params = [];
	protected $return;
	private $opt = [];
	
	public function summary(){
		list($summary) = explode(PHP_EOL,$this->document());
		return $summary;
	}
	public function add_params(\ebi\man\DocParam $p){
		$this->params[] = $p;
	}
	public function has_params(){
		return !empty($this->params);
	}
	public function param(){
		if(!empty($this->params)){
			return $this->params[0];
		}
		return new \ebi\man\DocParam(null,null);
	}
	
	public function set_opt($n,$val){
		$this->opt[$n] = $val;
	}
	public function has_opt($n){
		return (isset($this->opt[$n]) && !empty($this->opt[$n]));
	}
	public function opt($n,$def=null){
		return (isset($this->opt[$n])) ? $this->opt[$n] : $def;
	}
	
	
	public static function	parse($name,$src,$startpos=0){
		$info = new static();
		$info->name($name);
		
		$doc = ($startpos === 0) ? $src : substr($src,0,$startpos);
		$doc = trim(substr($doc,0,strrpos($doc,PHP_EOL)));
	
		if(substr($doc,-2) == '*'.'/'){
			$desc = '';
			
			foreach(array_reverse(explode(PHP_EOL,$doc)) as $line){
				if(strpos(ltrim($line),'/'.'**') !== 0){
					$desc = $line.PHP_EOL.$desc;
				}else{
					$desc = substr($line,strpos($line,'/**')+3).PHP_EOL.$desc;
					break;
				}
			}
			$doc = $desc;
		}
		$info->params(
			\ebi\man\DocParam::parse('param',$doc)
		);
		if(preg_match("/@return\s+([^\s]+)(.*)/",$doc,$m)){
			$info->return(new \ebi\man\DocParam(
				'return',
				$m[1],
				$m[2]
			));
		}
		$info->document(
			trim(preg_replace('/@.+/','',
				preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace('*'.'/','',$doc))
			))
		);
		return $info;
	}
}
