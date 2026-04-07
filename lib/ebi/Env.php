<?php
namespace ebi;

class Env{
	private array $vars = [];
	
	public function __construct(array $vars=[]){
		$this->vars = $vars;
	}
	
	/**
	 * 値があれば返す
	 * @param mixed $default
	 * @return mixed
	 */
	public function get(string $name, $default=''){
		if(array_key_exists($name,$this->vars)){
			return $this->vars[$name];
		}
		if(isset($_ENV[$name]) && $_ENV[$name] != ''){
			return $_ENV[$name];
		}
		if(isset($_SERVER[$name]) && $_SERVER[$name] != ''){
			return $_SERVER[$name];
		}
		if(getenv($name) !== false && getenv($name) != ''){
			return getenv($name);
		}
		switch($name){
			case 'HOSTNAME':
			case 'HTTP_HOST':
			case 'SERVER_NAME':
				$hostname = gethostname();
				if($hostname !== false) return $hostname;
				break;
			case 'USER':
			case 'USERNAME':
			case 'LOGNAME':
				$user = get_current_user();
				if($user !== '') return $user;
				break;
			case 'PWD':
			case 'DOCUMENT_ROOT':
				$cwd = getcwd();
				if($cwd !== false) return $cwd;
				break;
		}
		return $default;
	}
}