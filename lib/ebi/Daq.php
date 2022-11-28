<?php
namespace ebi;

class Daq{
	private static int $count = 0;
	private ?string $sql;
	private array $vars = [];
	private ?string $id;

	public function __construct($sql=null, array $vars=[], ?string $id_name=null){
		$this->sql = $sql;
		$this->id = $id_name;
		
		foreach($vars as $k => $v){
			$this->vars[$k] = is_bool($v) ? (($v === true) ? 1 : 0) : $v;
		}		
	}

	public function id(): string{
		return $this->id;
	}
	public function sql(): string{
		return $this->sql;
	}
	public function ar_vars(): array{
		return (empty($this->vars) ? [] : $this->vars);
	}
	public function is_id(): bool{
		return !empty($this->id);
	}
	public function is_vars(): bool{
		return !empty($this->vars);
	}
	public function unique_sql(): string{
		$rep = $match = [];
		$sql = $this->sql();

		if(preg_match_all("/[ct][\d]+/",$this->sql,$match)){
			foreach($match[0] as $m){
				if(!isset($rep[$m])) $rep[$m] = 'q'.self::$count++;
			}
			foreach($rep as $key => $value){
				$sql = str_replace($key,$value,$sql);
			}
		}
		return $sql;
	}
}