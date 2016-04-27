<?php
namespace ebi\Dt;
/**
 * DT用のヘルパー
 * @author tokushima
 *
 */
class Helper{
	/**
	 * パッケージ名の文字列表現
	 * @param string $p 
	 * @return string
	 */
	public function package_name($p){
		$p = str_replace(['/','\\'],['.','.'],$p);
		if(substr($p,0,1) == '.'){
			$p = substr($p,1);
		}
		return $p;
	}	
	/**
	 * 型の文字列表現を返す
	 * @param string $class
	 */
	public function type($class){
		if(preg_match('/[A-Z]/',$class)){
			switch(substr($class,-2)){
				case '{}':
				case '[]': $class = substr($class,0,-2);
			}
			$class = str_replace('\\','.',$class);
			
			if(substr($class,0,1) == '.'){
				$class = substr($class,1);
			}
			return $class;
		}
		return null;
	}
	/**
	 * 加算
	 * @param number $i
	 * @param number $add
	 * @return number
	 */
	public function calc_add($i,$add=1){
		return $i + $add;
	}
	/**
	 * プロパティへのアクセサ
	 * @param \ebi\Dao $obj
	 * @param string $prop_name
	 * @param string $ac
	 */
	public function acr(\ebi\Dao $obj,$prop_name,$ac='fm'){
		return $obj->{$ac.'_'.$prop_name}();
	}
	/**
	 * プロパティ一覧
	 * @param \ebi\Dao $obj
	 */
	public function props(\ebi\Dao $obj){
		$props = array_keys($obj->props());
		
		foreach($props as $i => $n){
			$type = $obj->prop_anon($n,'type','string');
			
			if(!preg_match('/^[a-z]+$/',$type)){
				unset($props[$i]);
			}
		}
		return $props;
	}
	/**
	 * 更新可能なプロパティ一覧
	 * @param \ebi\Dao $obj
	 * @return Ambigous <multitype:, unknown>
	 */
	public function update_props(\ebi\Dao $obj){
		$rtn = [];
		foreach($obj->props() as $k => $v){
			if($obj->prop_anon($k,'cond') === null && $obj->prop_anon($k,'extra') !== true){
				$rtn[] = $k;
			}
		}
		return $rtn;
	}
	/**
	 * Daoモデルからprimaryのrequest queryの文字列表現を返す
	 * @param \ebi\Dao $obj
	 * @return string
	 */
	public function primary_query(\ebi\Dao $obj){
		$result = [];
		foreach($this->props($obj) as $prop){
			if($obj->prop_anon($prop,'primary') === true && $obj->prop_anon($prop,'extra') !== true && $obj->prop_anon($prop,'cond') === null){
				$result[] = "primary[".$prop."]=".$obj->{$prop}();
			}
		}
		return implode("&",$result);
	}
	/**
	 * DaoモデルからprimaryのHTML form(hidden)の文字列表現を返す
	 * @param \ebi\Dao $obj
	 * @return string
	 */
	public function primary_hidden(\ebi\Dao $obj){
		$result = [];		
		foreach(array_keys($obj->props()) as $prop){
			if($obj->prop_anon($prop,'primary') === true && $obj->prop_anon($prop,'extra') !== true && $obj->prop_anon($prop,'cond') === null){
				$result[] = '<input type="hidden" name="primary['.$prop.']" value="'.$obj->{$prop}().'" />';
			}
		}
		return implode(PHP_EOL,$result);
	}
	/**
	 * プロパティがprimaryアノテーションを持つか
	 * @param object $obj
	 * @return boolean
	 */
	public function has_primary($obj){
		foreach(array_keys($obj->props()) as $prop){
			if($obj->prop_anon($prop,'primary') === true){
				return true;
			}
		}
		return false;
	}
	/**
	 * 検索用フォーム生成
	 * @param \ebi\Dao $obj
	 * @param string $name
	 * @return string
	 */
	public function filter(\ebi\Dao $obj,$name){
		if($obj->prop_anon($name,'master') !== null){
			$options = [];
			$options[] = '<option value=""></option>';
			$master = $obj->prop_anon($name,'master');
			
			if(!empty($master)){
				$master = str_replace('.',"\\",$master);
				
				if($master[0] !== "\\"){
					$master = "\\".$master;
				}
				$r = new \ReflectionClass($master);

				$mo = $r->newInstanceArgs();
				$primarys = $mo->primary_columns();
				
				if(sizeof($primarys) != 1){
					return sprintf('<input name="%s" type="text" />',$name);
				}
				$primary = array_shift($primarys);
				$pri = $primary->name();
				
				foreach($master::find() as $dao){
					$options[] = sprintf('<option value="%s">%s</option>',$dao->{$pri}(),(string)$dao);
				}
			}
			return sprintf('<select name="%s">%s</select>',$name,implode('',$options));
		}else{
			$type = $obj->prop_anon($name,'type');
			
			switch($type){
				case 'boolean':
					$options = [];
					$options[] = '<option value=""></option>';
					
					foreach(['true','false'] as $choice){
						$options[] = sprintf('<option value="%s">%s</option>',$choice,$choice);
					}
					return sprintf('<select name="search_%s_%s">%s</select>',$type,$name,implode('',$options));
				case 'timestamp':
					return sprintf('<input name="search_%s_from_%s" type="text" placeholder="YYYY/MM/DD HH:MI:SS" />',$type,$name).
					' 〜 '.
					sprintf('<input name="search_%s_to_%s" type="text" placeholder="YYYY/MM/DD HH:MI:SS" />',$type,$name);
				case 'date':
					return sprintf('<input name="search_%s_from_%s" type="text" placeholder="YYYY/MM/DD" />',$type,$name).
						' 〜 '.
						sprintf('<input name="search_%s_to_%s" type="text" placeholder="YYYY/MM/DD" />',$type,$name);
				default:
					if(empty($type) || preg_match('/^[a-z]+$/',$type)){
						return sprintf('<input name="search_%s_%s" type="text" />',$type,$name);
					}
			}
		}
	}
	/**
	 * 登録用フォーム生成
	 * @param \ebi\Dao $obj
	 * @param string $name
	 * @return string
	 */
	public function form(\ebi\Dao $obj,$name){
		if($obj->prop_anon($name,'master') !== null){
			$options = [];
			if(!$obj->prop_anon($name,'require')){
				$options[] = '<option value=""></option>';
			}
			$master = $obj->prop_anon($name,'master');
			if(!empty($master)){
				$master = str_replace('.',"\\",$master);
				if($master[0] !== "\\") $master = "\\".$master;
	
				try{
					$r = new \ReflectionClass($master);
				}catch(\ReflectionException $e){
					$self = new \ReflectionClass(get_class($obj));
					$r = new \ReflectionClass("\\".$self->getNamespaceName().$master);
				}
				$mo = $r->newInstanceArgs();
				$primarys = $mo->primary_columns();
				if(sizeof($primarys) != 1) return sprintf('<input name="%s" type="text" class="form-control" />',$name);
				foreach($primarys as $primary) break;
				$pri = $primary->name();
				foreach(call_user_func_array([$mo,'find'],[]) as $dao){
					$options[] = sprintf('<option value="%s">%s</option>',$dao->{$pri}(),(string)$dao);
				}
			}
			return sprintf('<select name="%s" class="form-control">%s</select>',$name,implode('',$options));
		}else if($obj->prop_anon($name,'save',true)){
			switch($obj->prop_anon($name,'type')){
				case 'serial': 
					return sprintf(
						'<input name="%s" type="text" disabled="disabled" class="form-control" />'.
						'<input name="%s" type="hidden" />',
						$name,
						$name
					);
				case 'text':
					return sprintf(
						'<textarea name="%s" style="height:10em;" class="form-control"></textarea>',
						$name
					);
				case 'boolean':
					$options = [];
					
					if(!$obj->prop_anon($name,'require')){
						$options[] = '<option value=""></option>';
					}
					foreach(['true','false'] as $choice){
						$options[] = sprintf('<option value="%s">%s</option>',$choice,$choice);
					}
					return sprintf('<select name="%s" class="form-control">%s</select>',$name,implode('',$options));
				default:
					return sprintf('<input name="%s" type="text" class="form-control" rtdt:type="%s" />',$name,$obj->prop_anon($name,'type','string'));
			}
		}
	}
	/**
	 * print_r
	 * @param mixed $obj
	 */
	public function dump($obj){
		$result = [];
		foreach($obj as $k => $v){
			if(isset($obj[$k])){
				if(!is_array($obj[$k]) || !empty($obj[$k])){
					$result[$k] = $v;
				}
			}
		}
		$value= print_r($result,true);
		$value = str_replace('=>'.PHP_EOL,': ',trim($value));
		$value = preg_replace('/\[\d+\]/','&nbsp;&nbsp;\\0',$value);
		return implode(PHP_EOL,array_slice(explode(PHP_EOL,$value),2,-1));
	}
	/**
	 * Confが定義されているか
	 * @param string $package
	 * @param string $key
	 * @return boolean
	 */
	public function has_conf($package,$key){
		return \ebi\Conf::exists($package, $key);
	}
	/**
	 * パッケージが指定したクラスのサブクラスに属するか
	 * @param string $package 
	 * @param string $class 
	 */
	public function is_subclass_of($package,$class){
		$package = '\\'.str_replace('.','\\',$package);
		$class = '\\'.str_replace('.','\\',$class);
		
		return is_subclass_of($package,$class);
	}
	
	/**
	 * makedown
	 * @param string $v
	 * @return string
	 */
	public function md2html($v){
		$lines = [];
		$pre = $on = false;
		$explode_lines = explode(PHP_EOL,$v.PHP_EOL);
		
		while(!empty($explode_lines)){
			$line = array_shift($explode_lines);
			
			if(!empty($line)){
				$act = html_entity_decode((substr($line,-6) == '<br />') ? substr($line,0,-6) : $line);
				
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
				
				$trim_act = trim($act);
				if(preg_match('/^([#]+)(.+)$/',$trim_act,$m)){
					$hn = strlen($m[1]);
					$line = '<h'.$hn.'>'.$m[2].'</h'.$hn.'>';
				}else if(preg_match('/^>(.+)$/',$trim_act,$m)){
					$line = '<blockquote>'.$m[1].'</blockquote>';
				}else if(preg_match('/^-[-]+$/',$trim_act)){
					$line = '<hr />';
				}else if(preg_match('/``[`]+(.*)$/',$trim_act)){
					$this->md_pre($line, $explode_lines, $lines);
					$line = '';
				}else if(preg_match('/^\|.+\|$/',$trim_act)){
					$this->md_table($line, $explode_lines, $lines);
					$line = '';
				}
			}
			$lines[] = $line;
		}
		return implode(PHP_EOL,$lines);
	}
	private function md_pre($line,&$explode_lines,&$lines){
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
				// TODO
				$act = html_entity_decode((substr($line,-6) == '<br />') ? substr($line,0,-6) : $line);
				$act = str_replace("\t",'    ',$act);
				
				$pre_lines[] = $act;
			}
		}
	}
	private function md_table($line,&$explode_lines,&$lines){
		$table = [];
		$table_head = false;
		array_unshift($explode_lines,$line);
		
		while(!empty($explode_lines)){
			$line = array_shift($explode_lines);
			// TODO
			$line = trim(html_entity_decode((substr($line,-6) == '<br />') ? substr($line,0,-6) : $line));
			
			
			if(preg_match('/^\|.+\|$/',$line)){
				if(!$table_head && sizeof($table) == 1 && preg_match('/^[\|\-\040]+$/',$line)){
					$table_head = true;
				}else{
					$table[] = $line;
				}
			}else{
				if(!empty($table)){
					$lines[] = '<table>';
						
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
}
