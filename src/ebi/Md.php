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
		
		$lines = [];
		$pre = $on = false;		
		$explode_lines = explode(PHP_EOL,$v);
	
		while(!empty($explode_lines)){
			$line = array_shift($explode_lines);
				
			if(!empty($line)){
				foreach(['\*\*'=>'strong','__'=>'em','~~'=>'s'] as $p => $t){
					if(preg_match_all('/'.$p.'(.+?)'.$p.'/',$line,$m)){
						foreach($m[1] as $k =>$v){
							$line = str_replace($m[0][$k],'<'.$t.'>'.$v.'</'.$t.'>',$line);
						}
					}
				}
				if(substr($line,0,3) != '```' && preg_match_all('/([`]+)(.+?)\\1/',$line,$m)){
					foreach($m[2] as $k =>$v){
						$line = str_replace($m[0][$k],'<code>'.$v.'</code>',$line);
					}
				}
				if(preg_match_all('/\!\[(.*?)\]\((.+?)\)/',$line,$m)){
					foreach($m[1] as $k =>$v){
						$line = str_replace($m[0][$k],sprintf('<img src="%s" alt="%s" />',$m[2][$k],$m[1][$k]),$line);
					}
				}
				if(preg_match_all('/\[(.*?)\]\((.+?)\)/',$line,$m)){
					foreach($m[1] as $k =>$v){
						$line = str_replace($m[0][$k],sprintf('<a href="%s">%s</a>',$m[2][$k],$m[1][$k]),$line);
					}
				}
	
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
		$r = implode(PHP_EOL,$lines);
		
		foreach($escape as $k => $e){
			$r = str_replace('@%'.$k,$e,$r);
		}
		return $r;
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
		$list = [];
		$splen = 0;
		array_unshift($explode_lines,$line);		

		while(!empty($explode_lines)){
			$line = array_shift($explode_lines);
			
			if(preg_match('/^([\t\040]*)\*(.+)$/',$line,$m)){
				$sp = strlen(str_replace('	','    ',$m[1]));
				
				if($splen > $sp){
					$splen = $sp;
					$lines[] = '<li><ul>';
				}else if($splen < $sp){
					$splen = $sp;
					$lines[] = '</ul></li>';
				}
				$lines[] = '<li>'.$m[2].'</li>';
			}else{
				$lines[] = '</ul>';
				
				array_unshift($explode_lines,$line);				
				return;
			}
		}
	}
}