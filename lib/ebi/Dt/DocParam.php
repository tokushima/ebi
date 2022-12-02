<?php
namespace ebi\Dt;

class DocParam extends \ebi\Obj implements \ebi\Dt\DocOptIf{
	protected string $name = '';
	protected string $type = '';
	protected ?string $summary = '';
	private $opt = [];
	
	public function __construct(string $name, string $type, string $summary='', $opt=[]){
		$this->name = trim($name);
		$this->type = trim($type);
		$this->summary = trim($summary);
		
		$this->opt = $opt;
	}
	public function set_opt(string $n, $val): void{
		$this->opt[$n] = $val;
	}
	public function opt(string $n, $def=null){
		return (isset($this->opt[$n])) ? $this->opt[$n] : $def;
	}
	
	public function is_type_class(): bool{
		return (bool)preg_match('/[A-Z]/',$this->type);
	}
	public function fm_type(): string{
		if(preg_match('/[A-Z]/',$this->type)){
			$type = $this->type;
			
			return $type;
		}
		return $this->type;
	}
	public function plain_type(): string{
		$type = $this->fm_type();
		
		switch(substr($type,-2)){
			case '{}':
			case '[]': $type = substr($type,0,-2);
		}
		return $type;
	}
	public static function parse(string $varname, string $doc): array{
		$result = $m = [];
		
		if(preg_match_all("/@".$varname."\s+([^\s]+)\s+\\$(\w+)(.*)/",$doc,$m)){
			foreach(array_keys($m[2]) as $n){
				$summary = $m[3][$n];
				$opt = [];
				
				if(strpos($summary,'@[') !== false){
					[$summary, $anon] = explode('@[',$summary,2);
					
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