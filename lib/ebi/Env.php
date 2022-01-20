<?php
namespace ebi;
/**
 * 環境変数
 */
class Env{
	private $vars = [];
	
	public function __construct(array $vars=[]){
		$this->vars = $vars;
	}
	
	/**
	 * 値があれば返す
	 */
	public function get(string $name, $default=null){
		if(array_key_exists($name,$this->vars)){
			return $this->vars[$name];
		}
		return (isset($_ENV[$name]) && $_ENV[$name] != '') ? 
			$_ENV[$name] : 
			(
				(isset($_SERVER[$name]) && $_SERVER[$name]  != '') ? 
					$_SERVER[$name] : 
					(
						(getenv($name) !== false && getenv($name) != '') ? 
							getenv($name) : 
							$default
					)
			);
	}
}