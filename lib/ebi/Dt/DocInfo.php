<?php

namespace ebi\Dt;
/**
 * @var string $name
 * @var text $document
 * @var \ebi\Dt\DocParam[] $params
 * @var \ebi\Dt\DocParam $return
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
		list($summary) = explode(PHP_EOL,trim($this->document()));
		return $summary;
	}
	public function add_params(\ebi\Dt\DocParam $p){
		$this->params[] = $p;
	}
	public function has_params(){
		return !empty($this->params);
	}
	public function param(){
		if(!empty($this->params)){
			return $this->params[0];
		}
		return new \ebi\Dt\DocParam(null,null);
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
	
	
	public static function	parse($name,$src,$docendpos=0){
		$info = new static();
		$info->name($name);

		if($docendpos > 0){
			$doc = trim(substr($src,0,$docendpos));
			
			$startpos = strrpos($doc,'/**');
			
			if($startpos !== false){
				$doc = substr($doc,$startpos);
				
				if(preg_match('/\/\*\*(.+?)\*\//s',$doc,$m)){
					$doc = preg_replace('/^[\s]*\*[\s]{0,1}/m','',$m[1]);
				}else{
					$doc = '';
				}
			}else{
				$doc = '';
			}
		}else{
			$doc = $src;
		}

		$params = \ebi\Dt\DocParam::parse('param',$doc);
		
		if(!empty($params)){
			$info->params($params);
		}
		if(preg_match("/@return\s+([^\s]+)(.*)/",$doc,$m)){
			$info->return(new \ebi\Dt\DocParam(
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
