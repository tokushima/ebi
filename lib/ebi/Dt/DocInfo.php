<?php

namespace ebi\Dt;
/**
 * @var string $name
 * @var text $document
 * @var \ebi\Dt\ParamInfo[] $params
 * @var \ebi\Dt\ParamInfo $return
 * @var string $version
 */
class DocInfo extends \ebi\Obj{
	protected string $name = '';
	protected string $document = '';
	protected array $params = [];
	protected ?\ebi\Dt\ParamInfo $return = null;
	protected string $version = '';
	private array $opt = [];
	
	public function set_opt(string $n, $val): void{
		$this->opt[$n] = $val;
	}
	public function opt(string $n, $def=null){
		return (isset($this->opt[$n])) ? $this->opt[$n] : $def;
	}

	public function has_opt(string $n): bool{
		return (isset($this->opt[$n]) && !empty($this->opt[$n]));
	}

	public function summary(): string{
		[$summary] = explode(PHP_EOL,trim($this->document()));
		return $summary;
	}
	public function add_params(\ebi\Dt\ParamInfo $p): void{
		$this->params[] = $p;
	}
	public function reset_params(array $new=[]): void{
		$this->params = $new;
	}
	public function has_params(): bool{
		return !empty($this->params);
	}
	public function param(): \ebi\Dt\ParamInfo{
		if(!empty($this->params)){
			return $this->params[0];
		}
		return new \ebi\Dt\ParamInfo('', '');
	}
		
	public static function parse(string $name, string $src, int $docendpos=0): self{
		$info = new static();
		$info->name($name);
		
		if($docendpos > 0){
			$doc = trim(substr($src,0,$docendpos));
			
			// 直上のコメントのみ有効
			if(substr_count(substr($doc,strrpos($doc,'*/')),PHP_EOL) == 1){
				$startpos = strrpos($doc,'/**');
				
				if($startpos !== false){
					$m = [];
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
				$doc = '';
			}
		}else{
			$doc = $src;
		}
		
		$params = \ebi\Dt\ParamInfo::parse('param',$doc);

		if(empty($params)){
			$params = \ebi\Dt\ParamInfo::parse_var($doc);
		}
		if(!empty($params)){
			$info->params($params);
		}
		if(preg_match("/@return\s+([^\s]+)(.*)/",$doc,$m)){
			$info->return(new \ebi\Dt\ParamInfo(
				'return',
				$m[1],
				$m[2]
			));
		}
		
		$match = [];
		if(preg_match("/@version\s+([^\s]+)/",$doc,$match)){
			$info->version(trim($match[1]));
		}
		$info->document(
			trim(preg_replace('/@.+/','',
				preg_replace("/^[\s]*\*[\s]{0,1}/m",'',str_replace('*'.'/','',$doc))
			))
		);		
		return $info;
	}
}
