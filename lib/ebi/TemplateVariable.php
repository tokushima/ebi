<?php
namespace ebi;
/**
 * 
 * @author tokushima
 *
 */
trait TemplateVariable{	
	public function htmlencode($v){
		if(!empty($v) && is_string($v)){
			$v = mb_convert_encoding($v,'UTF-8',mb_detect_encoding($v));
			return htmlentities($v,ENT_QUOTES,'UTF-8');
		}
		return $v;
	}
	public function print_variable($v){
		print($v);
	}
	protected function default_vars(){
		return ['_t_'=>new self()];
	}
	protected function parse_print_variable($src){
		foreach($this->match_variable($src) as $variable){
			$name = $this->parse_plain_variable($variable);
			$value = $this->php_exception_catch('<?php $_t_->print_variable('.$name.'); ?>');
			$src = str_replace([$variable."\n",$variable],[$value."<?php 'PLRP'; ?>\n\n",$value],$src);
			$src = str_replace($variable,$value,$src);
		}
		return $src;
	}
	protected function parse_plain_variable($src){
		
		while(true){
			$array = $this->match_variable($src);
			
			if(sizeof($array) <= 0){
				break;
			}
			
			foreach($array as $v){
				$tmp = $v;
				$match = [];
				
				if(preg_match_all("/([\"\'])([^\\1]+?)\\1/",$v,$match)){
					foreach($match[2] as $value){
						$tmp = str_replace($value,str_replace('.','__PERIOD__',$value),$tmp);
					}
				}
				$src = str_replace($v,preg_replace('/([\w\)\]])\./','\\1->',substr($tmp,1,-1)),$src);
			}
		}
		return str_replace('[]','',str_replace('__PERIOD__','.',$src));
	}
	protected function variable_string($src){
		return (empty($src) || isset($src[0]) && $src[0] == '$') ? $src : '$'.$src;
	}
	protected function php_exception_catch($tag){
		return '<?php try{ ?>'
		.$tag
		.'<?php }catch(\Exception $e){} ?>';
	}
	protected function match_variable($src){
		$hash = $vars = [];
		while(preg_match("/({(\\$[\$\w][^\t]*)})/s",$src,$vars,PREG_OFFSET_CAPTURE)){
			list($value,$pos) = $vars[1];
			if($value == '') break;
			if(substr_count($value,'}') > 1){
				for($i=0,$start=0,$end=0;$i<strlen($value);$i++){
					if($value[$i] == '{'){
						$start++;
					}else if($value[$i] == '}'){
						if($start == ++$end){
							$value = substr($value,0,$i+1);
							break;
						}
					}
				}
			}
			$length	= strlen($value);
			$src = substr($src,$pos + $length);
			$hash[sprintf('%03d_%s',$length,$value)] = $value;
		}
		krsort($hash,SORT_STRING);
		return $hash;
	}
}