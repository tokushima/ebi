<?php
namespace ebi\Dt;
/**
 * 
 * @author tokushima
 * @var string $name
 * @var string $type
 * @var string $summary
 */
class DocParam extends \ebi\Obj{
	protected $name;
	protected $type;
	protected $summary;
	private $opt = [];
	
	public function __construct($name,$type,$summary='',$opt=[]){
		$this->name = trim($name);
		$this->type = trim($type);
		$this->summary = trim($summary);
		
		$this->opt = $opt;
	}
	public function set_opt($n,$val){
		$this->opt[$n] = $val;
	}
	public function opt($n,$def=null){
		return (isset($this->opt[$n])) ? $this->opt[$n] : $def;
	}
	
	public function is_type_class(){
		return (boolean)preg_match('/[A-Z]/',$this->type);
	}
	public function fm_type(){
		if(preg_match('/[A-Z]/',$this->type)){
			$type = $this->type;
			
			return $type;
		}
		return $this->type;
	}
	public function plain_type(){
		$type = $this->fm_type();
		
		switch(substr($type,-2)){
			case '{}':
			case '[]': $type = substr($type,0,-2);
		}
		return $type;
	}
	public static function parse($varname,$doc){
		$result = $m = [];
		
		if(preg_match_all("/@".$varname."\s+([^\s]+)\s+\\$(\w+)(.*)/",$doc,$m)){
			foreach(array_keys($m[2]) as $n){
				$summary = $m[3][$n];
				$opt = [];
				
				if(strpos($summary,'@[') !== false){
					list($summary,$anon) = explode('@[',$summary,2);
					
					try{
						$opt = \ebi\Annotation::activation('@['.$anon);
					}catch(\ParseError $e){
						throw new \ebi\exception\InvalidAnnotationException('annotation error : `'.'@['.$anon.'`');
					}
				}
				$result[] = new static(
					$m[2][$n],
					$m[1][$n],
					$summary,
					$opt
				);
			}
		}
		return $result;
	}
}