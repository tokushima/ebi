<?php
namespace ebi;
/**
 * Makedown
 * @author tokushima
 *
 */
class Md{
	/**
	 * output html
	 * @param string $v
	 */
	public function html($v){
		$escape = str_split('|*-`#_');
		$v = htmlentities($v).PHP_EOL;
		
		foreach($escape as $k => $e){
			$v = str_replace('\\'.$e,'@%'.$k,$v);
		}
		
		$lines = $m = [];
		$explode_lines = explode(PHP_EOL,$v);
	
		while(!empty($explode_lines)){
			$line = array_shift($explode_lines);
				
			if(!empty($line)){
				$trim_line = trim($line);
				
				if(!empty($trim_line)){
					if(preg_match('/^([#]+)(.+)$/',$trim_line,$m)){
						$hn = strlen($m[1]);
						$line = '<h'.$hn.'>'.$m[2].'</h'.$hn.'>';
					}else if(preg_match('/^>(.+)$/',$trim_line,$m)){
						$line = '<blockquote>'.$m[1].'</blockquote>';
					}else if(preg_match('/^-[-]+$/',$trim_line)){
						$line = '<hr />';
					}else if(preg_match('/``[`]+(.*)$/',$trim_line)){
						$this->html_pre($line, $explode_lines, $lines);
						$line = '';
					}else if(strpos($trim_line,'|')){
						$this->html_table($line, $explode_lines, $lines);
						$line = '';
					}else if($trim_line[0] == '*'){
						$this->html_list($line, $explode_lines, $lines);
						$line = '';
					}
				}
			}
			if(!empty($line)){
				$lines[] = $line.'<br />';
			}
		}
		$result = implode(PHP_EOL,$lines);
		
		if(preg_match_all('/([`]+)(.+?)\\1/',$result,$m)){
			foreach($m[2] as $k =>$v){
				$result = str_replace($m[0][$k],'<code>'.$v.'</code>',$result);
			}
		}
		
		foreach(['\*\*'=>'strong','__'=>'em','~~'=>'s'] as $p => $t){
			if(preg_match_all('/'.$p.'(.+?)'.$p.'/',$result,$m)){
				foreach($m[1] as $k =>$v){
					$result = str_replace($m[0][$k],'<'.$t.'>'.$v.'</'.$t.'>',$result);
				}
			}
		}
		if(preg_match_all('/\!\[(.*?)\]\((.+?)\)/',$result,$m)){
			foreach($m[1] as $k =>$v){
				$result = str_replace($m[0][$k],sprintf('<img src="%s" alt="%s" />',$m[2][$k],$m[1][$k]),$result);
			}
		}
		if(preg_match_all('/\[(.*?)\]\((.+?)\)/',$result,$m)){
			foreach($m[1] as $k =>$v){
				if(empty($m[1][$k])){
					$m[1][$k] = $m[2][$k];
				}
				$result = str_replace($m[0][$k],sprintf('<a href="%s">%s</a>',$m[2][$k],$m[1][$k]),$result);
			}
		}
		
		foreach($escape as $k => $e){
			$result = str_replace('@%'.$k,$e,$result);
		}
		return $result;
	}
	private function html_pre($line,&$explode_lines,&$lines){
		$pre_lines = [];
	
		while(!empty($explode_lines)){
			$line = array_shift($explode_lines);
				
			if(preg_match('/``[`]+(.*)$/',$line)){
				$lines[] = '<pre>';
	
				foreach($pre_lines as $p){
					$lines[] = $p;
				}
				$lines[] = '</pre>';
				return;
			}else{
				$pre_lines[] = str_replace("\t",'    ',$line);
			}
		}
	}
	private function html_table($line,&$explode_lines,&$lines){
		$table = [];
		$table_head = false;
		array_unshift($explode_lines,$line);
	
		while(!empty($explode_lines)){
			$line = trim(array_shift($explode_lines));
			
			if(strlen($line) > 2 && strpos($line,'|')){
				if($line[0] != '|'){
					$line = '|'.$line;
				}
				if(substr($line,-1)){
					$line = $line.'|';
				}
				if(!$table_head && sizeof($table) == 1 && preg_match('/^[\|\-\040\t]+$/',$line)){
					$table_head = true;
				}else{
					$table[] = $line;
				}
			}else{
				if(!empty($table)){
					$lines[] = '<table class="table">';
	
					if($table_head){
						$thead = array_shift($table);
						$lines[] = '<tr>'.substr(str_replace('|','</th><th>',$thead),5,-4).'</tr>';
					}
					foreach($table as $t){
						$t = str_replace('|','</td><td>',$t);
						$lines[] = '<tr>'.substr($t,5,-4).'</tr>';
					}
					$lines[] = '</table>';
					return;
				}
				return;
			}
		}
	}

	private function html_list($line,&$explode_lines,&$lines){
		array_unshift($explode_lines,$line);
		$efunc = null;

		$efunc = function(&$explode_lines,$index=1,$a=null) use(&$efunc){
			$result = $m = [];
		
			if(isset($a)){
				$result[] = $a;
			}
		
			while(!empty($explode_lines)){
				$line = array_shift($explode_lines);
				
				if(preg_match('/^([\t\040]*)\*(.+)$/',' '.$line,$m)){
					$sp = strlen(str_replace('	','    ',$m[1]));
					$v = ltrim($m[2]);
						
					if($sp == $index){
						$result[] = $v;
					}else if($sp > $index){
						$result[] = $efunc($explode_lines,$sp,$v);
					}else{
						array_unshift($explode_lines,$line);
						return $result;
					}
				}else{
					array_unshift($explode_lines,$line);
					break;
				}
			}
			return $result;
		};
		
		$ofunc = null;
		$ofunc = function($list,&$lines) use(&$ofunc){
			$lines[] = '<ul>';
		
			foreach($list as $v){
				if(is_array($v)){
					$line_c = [];
					$ofunc($v,$line_c);
					$lines[] = implode(PHP_EOL,$line_c);
				}else{
					$lines[] = '<li>'.$v.'</li>';
				}
			}
			$lines[] = '</ul>';
		};
		
		$ofunc($efunc($explode_lines),$lines);
	}
}