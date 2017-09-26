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
		$this->type = $this->type(trim($type));
		$this->summary = trim($summary);
		
		$this->opt = $opt;
	}
	public function set_opt($n,$val){
		$this->opt[$n] = $val;
	}
	public function opt($n,$def=null){
		return (isset($this->opt[$n])) ? $this->opt[$n] : $def;
	}
	private function type($type){
		$type = str_replace('.','\\',$type);
	
		if(substr($type,0,1) == '.'){
			$type = substr($type,1);
		}
		return $type;
	}
	
	public function is_type_class(){
		return (boolean)preg_match('/[A-Z]/',$this->type);
	}
	/**
	 * 型の文字列表現を返す
	 * @param string $class
	 */
	public function fm_type(){
		if(preg_match('/[A-Z]/',$this->type)){
			$type = $this->type;
			
			switch(substr($type,-2)){
				case '{}':
				case '[]': $type = substr($type,0,-2);
			}
			$type = str_replace('.','\\',$type);
			
			if(substr($type,0,1) != '\\'){
				$type = '\\'.$type;
			}
			return $type;
		}
		return $this->type;
	}
	public static function parse($varname,$doc){
		$result = [];
		
		if(preg_match_all("/@".$varname."\s+([^\s]+)\s+\\$(\w+)(.*)/",$doc,$m)){
			foreach(array_keys($m[2]) as $n){
				$summary = $m[3][$n];
				$opt = [];
				
				if(strpos($summary,'@[') !== false){
					list($summary,$anon) = explode('@[',$summary,2);
					$opt = \ebi\Annotation::activation('@['.$anon);
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