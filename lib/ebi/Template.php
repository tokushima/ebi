<?php
namespace ebi;
/**
 * テンプレートを処理する
 * @author tokushima
 * @var mixed{} $vars バインドされる変数
 * @var boolean $secure http://をhttps://に置換するか
 * @var string $media_url メディアファイルへのURLの基点
 */
class Template{
	use \ebi\Plugin,\ebi\TemplateVariable;
	
	private $secure = false;
	private $vars = [];
	private $media_url;

	private $base_dir;
	
	/**
	 * メディアURLをhttpsにする
	 * @param boolean $bool
	 * @return \ebi\Template
	 */
	public function secure($bool){
		$this->secure = (boolean)$bool;
		return $this;
	}
	/**
	 * 変数をバインドする
	 * @param string $k
	 * @param mixed $v
	 * @return \ebi\Template
	 */
	public function vars($k,$v){
		$this->vars[$k] = $v;
		return $this;
	}
	
	/**
	 * メディアURLを設定する
	 * @param string $v
	 * @return \ebi\Template
	 */
	public function media_url($v){
		$this->media_url = \ebi\Util::path_slash($v,null,true);
		return $this;
	}
	
	/**
	 * ファイルを読み込んで結果を返す
	 * @param string $file
	 * @return string
	 */
	public function read($filename,$base_dir=null){
		$this->base_dir = $base_dir ?? dirname(realpath($filename));
		$src = $this->read_src($filename);
		
		return $this->get($src,$base_dir);
	}
	/**
	 * 文字列から結果を返す
	 * @param string $src
	 * @return string
	 */
	public function get($src,$base_dir=null){
		$this->base_dir = $base_dir ?? getcwd();
		
		$src = $this->replace($src);
		$src = $this->exec($src);
		
		if(strpos($src,'rt:ref') !== false){
			$src = str_replace(['#PS#','#PE#'],['<?','?>'],$this->html_form($src));
			$src = $this->exec($this->parse_print_variable($this->html_input($src)));
		}
		/**
		 * 実行後処理
		 * @param string $src
		 * @return $src
		 */
		foreach($this->get_object_plugin_funcs('after_exec_template') as $o){
			$src = static::call_func($o,$src);
		}
		return $src;
	}
	/**
	 * 出力する
	 * @param string $file
	 */
	public function output($file,$base_dir=null){
		print($this->read($file,$base_dir));
		exit;
	}
	
	private function replace($src){
		$src = preg_replace("/([\w])\->/","\\1__PHP_ARROW__",$src);
		$src = str_replace(["\\\\","\\\"","\\'"],['__ESC_DESC__','__ESC_DQ__','__ESC_SQ__'],$src);
		$src = $this->replace_xtag($src);
		/**
		 * 初期処理
		 * @param string $src
		 * @return $src
		 */
		foreach($this->get_object_plugin_funcs('init_template') as $o){
			$src = static::call_func($o,$src);
		}
		$src = $this->rtcomment($this->rtinclude($this->rtblock($src)));
		/**
		 * 前処理
		 * @param string $src
		 * @return $src
		 */		
		foreach($this->get_object_plugin_funcs('before_template') as $o){
			$src = static::call_func($o,$src);
		}
		$src = $this->rtpaginator($this->rtif($this->rtloop($this->html_form($this->html_list($src)))));
		/**
		 * 後処理
		 * @param string $src
		 * @return $src
		 */		
		foreach($this->get_object_plugin_funcs('after_template') as $o){
			$src = static::call_func($o,$src);
		}
		$src = str_replace('__PHP_ARROW__','->',$src);
		$src = $this->parse_print_variable($src);
		$php = [' ?>','<?php ','->'];
		$str = ['__PHP_TAG_END__','__PHP_TAG_START__','__PHP_ARROW__'];
		$src = str_replace($php,$str,$src);
		
		$keys = $tags = [];
		if($bool = $this->html_script_search($src,$keys,$tags)){
			$src = str_replace($tags,$keys,$src);
		}
		$src = $this->parse_url($src,$this->media_url);
		
		if($bool){
			$src = str_replace($keys,$tags,$src);
		}
		$src = str_replace($str,$php,$src);
		$src = str_replace(['__ESC_DQ__','__ESC_SQ__','__ESC_DESC__'],["\\\"","\\'","\\\\"],$src);
		return $src;		
	}
	private function exec($_src_){
		/**
		 * 実行直前処理
		 * @param string $src
		 * @return $src
		 */
		foreach($this->get_object_plugin_funcs('before_exec_template') as $o){
			$_src_ = static::call_func($o,$_src_);
		}
		foreach($this->default_vars() as $k => $v){
			$this->vars($k,$v);
		}

		try{
			ob_start();
			if(is_array($this->vars) && !empty($this->vars)){
				extract($this->vars);
			}
			eval('?>'.$_src_);
			$_eval_src_ = ob_get_clean();
		}catch(\ParseError $e){
			ob_clean();
			throw new \ebi\exception\InvalidTemplateException($e->getMessage());
		}
		return $_eval_src_;
	}
	private function replace_xtag($src){
		$m = [];
		if(preg_match_all("/<\?(?!php[\s\n])[\w]+ .*?\?>/s",$src,$m)){
			foreach($m[0] as $value){
				$src = str_replace($value,'#PS#'.substr($value,2,-2).'#PE#',$src);
			}
		}
		return $src;
	}
	private function parse_url($src,$media){
		if(!empty($media) && substr($media,-1) !== '/'){
			$media = $media.'/';
		}
		$secure_base = ($this->secure) ? str_replace('http://','https://',$media) : null;
		$m = [];
		
		if(preg_match_all("/<([^<\n]+?[\s])(src|href|background)[\s]*=[\s]*([\"\'])([^\\3\n]+?)\\3[^>]*?>/i",$src,$m)){
			foreach($m[2] as $k => $p){
				list($url) = explode('?',$m[4][$k]);		
				if(strpos($url,'$') === false){
					$t = null;
					if(strtolower($p) === 'href'){
						list($t) = (preg_split("/[\s]/",strtolower($m[1][$k])));
					}
					$src = $this->replace_parse_url($src,(($this->secure && $t !== 'a') ? $secure_base : $media),$m[0][$k],$m[4][$k]);
				}
			}
		}
		if(preg_match_all("/[^:]:[\040]*url\(([^\\$\n]+?)\)/",$src,$m)){
			if($this->secure){
				$media = $secure_base;
			}
			foreach(array_keys($m[1]) as $key){
				$src = $this->replace_parse_url($src,$media,$m[0][$key],$m[1][$key]);
			}
		}
		return $src;
	}
	private function replace_parse_url($src,$base,$dep,$rep){
		if(!preg_match("/(^\/\/)|(^[\w]+:\/\/)|(^__PHP_TAG_START)|(^\w+:)|(^[#\?])/",$rep)){
			$src = str_replace($dep,str_replace($rep,\ebi\Util::path_absolute($base,$rep),$dep),$src);
		}
		return $src;
	}
	private function read_src($filename){
		$src = \ebi\Util::file_read(\ebi\Util::path_absolute($this->base_dir,$filename));
		return (preg_match('/^http[s]*\:\/\//',$filename)) ? $this->parse_url($src,dirname($filename)) : $src;
	}
	private function rtinclude($src){
		try{
			while(true){
				$tag = \ebi\Xml::extract($src,'rt:include');
				$src = str_replace($tag->plain(),$this->read_src($tag->in_attr('href')),$src);
			}
		}catch(\ebi\exception\NotFoundException $e){
		}
		return $src;
	}
	private function rtblock($src){
		if(strpos($src,'rt:block') !== false || strpos($src,'rt:extends') !== false){
			$blocks = [];
			
			try{
				while(true){
					$extends = \ebi\Xml::extract($this->rtcomment($src),'rt:extends');
					$readxml = \ebi\Xml::anonymous($this->rtcomment($src));
					
					foreach($readxml->find('rt:block') as $b){
						$n = $b->in_attr('name');
						
						if(!empty($n) && !array_key_exists($n,$blocks)){
							$blocks[$n] = $b->value();
						}
					}
					$src = $this->replace_xtag($this->read_src($extends->in_attr('href')));
				}
			}catch(\ebi\exception\NotFoundException $e){
			}
			
			if(empty($blocks)){
				foreach(\ebi\Xml::anonymous($src)->find('rt:block') as $b){
					$src = str_replace($b->plain(),$b->value(),$src);
				}
			}else{
				try{
					while(true){
						$b = \ebi\Xml::extract($src,'rt:block');
						$n = $b->in_attr('name');
						$src = str_replace($b->plain(),(array_key_exists($n,$blocks) ? $blocks[$n] : $b->value()),$src);
					}
				}catch(\ebi\exception\NotFoundException $e){
				}
			}
		}
		return $src;
	}
	private function rtcomment($src){
		try{
			while(true){
				$tag = \ebi\Xml::extract($src,'rt:comment');
				$src = str_replace($tag->plain(),'',$src);
			}
		}catch(\ebi\exception\NotFoundException $e){
		}
		return $src;
	}
	private function rtloop($src){
		try{
			while(true){
				$tag = \ebi\Xml::extract($src,'rt:loop');
				$tag->escape(false);
				$value = $tag->value();				
				
				try{
					while(true){
						\ebi\Xml::extract($value,'rt:loop');
						$value = $this->rtloop($value);
					}
				}catch(\ebi\exception\NotFoundException $e){
				}
				$uniq = uniqid('');
				$param = ($tag->is_attr('param')) ? $this->variable_string($this->parse_plain_variable($tag->in_attr('param'))) : null;
				$varname = '$_'.$uniq;
				$var = '$'.$tag->in_attr('var','_v_'.$uniq);
				$key = '$'.$tag->in_attr('key','_k_'.$uniq);
				$counter = '$'.$tag->in_attr('counter','_c_'.$uniq);
				$limitname = '$_l_'.$uniq;
				$limit = $tag->in_attr('limit','0');
				
				$src = $this->php_exception_catch(str_replace(
					$tag->plain(),
					sprintf('<?php '
								.' %s = %s; '
								.' %s = 0; '
								.' %s = %s; '
								.' foreach(%s as %s => %s){'
									.' %s++; '
							.' ?>'
									.'%s'
							.'<?php '
								.' if(%s > 0 && %s <= %s){ break; }'
								.' } '
							.' ?>'
							,$varname,$param
							,$counter
							,$limitname,$limit
							,$varname,$key,$var
								,$counter
							,$value
								,$limitname,$limitname,$counter
					)
					,$src
				));
			}
		}catch(\ebi\exception\NotFoundException $e){
		}
		return $src;
	}
	private function rtif($src){
		try{
			while(true){
				$tag = \ebi\Xml::extract($src,'rt:if');
				$tag->escape(false);
				
				if(!$tag->is_attr('param')){
					throw new \ebi\exception\InvalidTemplateException('if');
				}
				$uniq = uniqid('$I');
				$arg1 = $this->variable_string($this->parse_plain_variable($tag->in_attr('param')));
				
				$src = str_replace(
					$tag->plain(),
					$this->php_exception_catch(
						sprintf(
							'<?php try{ %s=%s; }catch(\Exception $e){ %s=null; } ?>'.
							'<?php if(%s){ ?>',
							$uniq,$arg1,$uniq,
							$uniq
						).
						preg_replace('/<rt\:else[\s]*.*?>/i','<?php }else{ ?>',$tag->value()).'<?php } ?>'),
					$src
				);
			}
		}catch(\ebi\exception\NotFoundException $e){
		}
		return $src;
	}
	private function html_script_search($src,&$keys,&$tags){
		$keys = $tags = [];
		$uniq = uniqid('uniq');		
		$i = 0;
		
		foreach(\ebi\Xml::anonymous($src)->find('script') as $obj){
			if(!$obj->is_attr('src')){
				$keys[] = '__'.$uniq.($i++).'__';
				$tags[] = $obj->plain();
			}
		}
		return ($i > 0);
	}
	private function html_form($src){
		foreach(\ebi\Xml::anonymous($src)->find('form') as $obj){
			if($obj->in_attr('rt:aref') === 'true'){
				$obj->rm_attr('rt:aref');
				$obj->attr('rt:ref','true');
				
				if($obj->is_attr('rt:param')){
					// 評価を遅延させる
					$obj->attr('rt:param',str_replace('{$','{#',$obj->in_attr('rt:param')));
				}
				$src = str_replace($obj->plain(),$obj->get(),$src);
			}else if($this->is_reference($obj)){
				$obj->escape(false);

				if($obj->is_attr('rt:param')){
					$param = $this->variable_string($this->parse_plain_variable(str_replace('{#','{$',$obj->in_attr('rt:param'))));
					$uniq = uniqid('');
					$var = '$__form_var__'.$uniq;
					$k = '$__form_k__'.$uniq;
					$v = '$__form_v__'.$uniq;
					$tag = $this->php_exception_catch(sprintf(
							'<?php '
							.'%s=%s; '
							.'if( ( is_array(%s) || (is_object(%s) && %s instanceof \Traversable) ) ){'
							.' foreach(%s as %s => %s){'
							.'  if(preg_match(\'/^[a-zA-Z0-9_]+$/\',%s) && !isset($%s)){'
							.'   $%s = %s;'
							.'  }'
							.' }'
							.'}'
							.' ?>'
							,$var,$param
							,$var,$var,$var
							,$var,$k,$v
							,$k,$k,
							$k,$v
					)).PHP_EOL;
					$obj->rm_attr('rt:param');
					$obj->value($tag.$obj->value());
				}
				foreach($obj->find('input|select|textarea') as $tag){
					if(!$tag->is_attr('rt:ref') && ($tag->is_attr('name') || $tag->is_attr('id'))){
						switch(strtolower($tag->in_attr('type','text'))){
							case 'button':
							case 'submit':
								break;
							case 'file':
								$obj->attr('enctype','multipart/form-data');
								$obj->attr('method','post');
								break;								
							default:
								$tag->attr('rt:ref','true');
								$obj->value(str_replace($tag->plain(),$tag->get(),$obj->value()));
						}
					}
				}
				$src = str_replace($obj->plain(),$obj->get(),$src);
			}
		}
		return $this->html_input($src);
	}
	
	private function html_input($src){
		foreach(\ebi\Xml::anonymous($src)->find('input|textarea|select') as $obj){
			if('' != ($originalName = $obj->in_attr('name',$obj->in_attr('id','')))){
				$obj->escape(false);
				$type = strtolower($obj->in_attr('type','text'));
				$name = $this->parse_plain_variable($this->form_variable_name($originalName));
				$tagname = strtolower($obj->name());
				$change = $obj->is_attr('rt:ref');
				$uid = uniqid();
				$m = [];

				if(substr($originalName,-2) !== '[]'){
					if($type == 'checkbox'){
						if($obj->in_attr('rt:multiple','true') === 'true'){
							$obj->attr('name',$originalName.'[]');
						}
						$obj->rm_attr('rt:multiple');
						$change = true;
					}else if($obj->is_attr('multiple') || $obj->in_attr('multiple') === 'multiple'){
						$obj->attr('name',$originalName.'[]');
						$obj->rm_attr('multiple');
						$obj->attr('multiple','multiple');
						$change = true;
					}
				}else if($obj->in_attr('name') !== $originalName){
					$obj->attr('name',$originalName);
					$change = true;
				}
				if($obj->is_attr('rt:param')){
					switch($tagname){
						case 'select':
							$value = sprintf('<rt:loop param="%s" var="%s" key="%s">'
											.((trim($obj->value()) == '') ? '<option value="{$%s}">{$%s}</option>' : $obj->value())
											.'</rt:loop>'
											,$obj->in_attr('rt:param'),$obj->in_attr('rt:var','loop_var'.$uid),$obj->in_attr('rt:key','loop_key'.$uid)
											,$obj->in_attr('rt:key','loop_key'.$uid),$obj->in_attr('rt:var','loop_var'.$uid)
							);
							$obj->value($this->rtloop($value));
							if($obj->is_attr('rt:null')){
								$obj->value('<option value="">'.$obj->in_attr('rt:null').'</option>'.$obj->value());
							}
					}
					$obj->rm_attr('rt:param','rt:key','rt:var','rt:null');
					$change = true;
				}
								
				if($tagname == 'input'){
					if($this->is_reference($obj)){
						if($type == 'checkbox' || $type == 'radio'){
							$value = $this->parse_plain_variable($obj->in_attr('value','true'));
							$value = (substr($value,0,1) != '$') ? sprintf("'%s'",$value) : $value;
							$obj->rm_attr('checked');
							$obj->plain_attr($this->check_selected($name,$value,'checked'));
						}else{
							$obj->attr('value',$this->no_exception_str(sprintf('{$_t_.htmlencode(%s)}',
									((preg_match("/^\{\$(.+)\}$/",$originalName,$m)) ?
											'{$$'.$m[1].'}' :
											'{$'.$originalName.'}'))));
						}
						$change = true;
					}
				}else if($tagname == 'textarea'){
					if($this->is_reference($obj)){
						$obj->value($this->no_exception_str(
								sprintf('{$_t_.htmlencode(%s)}',((preg_match("/^{\$(.+)}$/",$originalName,$m)) ? 
								'{$$'.$m[1].'}' : 
								'{$'.$originalName.'}')
							)
						));
						$obj->close_empty(false);
						$change = true;
					}
				}else if($tagname == 'select'){
					if($this->is_reference($obj) || $obj->is_attr('value')){
						$select = $obj->value();
						$name = $this->parse_plain_variable($obj->in_attr('value',$name));
						$obj->rm_attr('value');
							
						foreach($obj->find('option') as $option){
							$option->escape(false);
							$value = $this->parse_plain_variable($option->in_attr('value'));
					
							if(empty($value) || $value[0] != '$'){
								$value = sprintf("'%s'",$value);
							}
							$option->rm_attr('selected');
							$option->plain_attr($this->check_selected($name,$value,'selected'));
							$select = str_replace($option->plain(),$option->get(),$select);
						}
						$obj->value($select);
						$obj->close_empty(false);
						$change = true;
					}
				}
				if($change){
					$src = str_replace($obj->plain(),$obj->get(),$src);
				}
			}
		}
		return $src;

	}
	private function check_selected($name,$value,$selected){
		return sprintf('<?php if('
					.((strpos($name,'->') === false) ? 'isset('.$name.') && ' : '')
					.'(%s === %s '
							.' || (!is_array(%s) && ctype_digit((string)%s) && (string)%s === (string)%s)'
							.' || ((%s === "true" || %s === "false") ? (%s === (%s == "true")) : false)'
							.' || in_array(%s,((is_array(%s)) ? %s : (is_null(%s) ? [] : [%s])),true) '
						.') '
					.'){ print(" %s=\"%s\""); } ?>' // no escape
					,$name,$value
					,$name,$name,$name,$value
					,$value,$value,$name,$value
					,$value,$name,$name,$name,$name
					,$selected,$selected
				);
	}
	private function html_list($src){
		$tags = $m = [];
		
		if(preg_match_all('/<(table|ul|ol)\s[^>]*rt\:/i',$src,$m,PREG_OFFSET_CAPTURE)){
			foreach($m[1] as $v){
				try{
					$tags[] = \ebi\Xml::extract(substr($src,$v[1]-1),$v[0]);
				}catch(\ebi\exception\NotFoundException $e){
				}
			}
			foreach($tags as $obj){
				$obj->escape(false);
				$name = strtolower($obj->name());
				$param = $obj->in_attr('rt:param');
				$value = sprintf('<rt:loop param="%s" var="%s" counter="%s" '
					.'key="%s" '
					.'>'
					,$param,$obj->in_attr('rt:var','loop_var'),$obj->in_attr('rt:counter','loop_counter')
					,$obj->in_attr('rt:key','loop_key')
				);
				$rawvalue = $obj->value();

				if($name == 'table'){
					try{
						$t = \ebi\Xml::extract($rawvalue,'tbody');
						$t->escape(false);
						$t->value($value.$t->value().'</rt:loop>');
						$value = str_replace($t->plain(),$t->get(),$rawvalue);
					}catch(\ebi\exception\NotFoundException $e){
						$value = $value.$rawvalue.'</rt:loop>';
					}
				}else{
					$value = $value.$rawvalue.'</rt:loop>';
				}
				$obj->value($this->html_list($value));
				$obj->rm_attr('rt:param','rt:key','rt:var','rt:counter');
				$src = str_replace($obj->plain(),$obj->get(),$src);
			}
		}
		return $src;
	}
	private function rtpaginator($src){
		return \ebi\Xml::find_replace($src,'rt:paginator',function($xml){
			$param = $this->variable_string($this->parse_plain_variable($xml->in_attr('param','paginator')));
			$navi = array_change_key_case(array_flip(explode(',',$xml->in_attr('nav','prev,next,first,last,counter'))));
			$counter = $xml->in_attr('counter',10);
			$lt = strtolower($xml->in_attr('lt','true'));
			$href = $xml->in_attr('href','?');
			
			$uniq = uniqid('');
			$counter_var = '$__counter__'.$uniq;
			$func = '';
			
			if($lt == 'false'){
				$func .= sprintf('<?php if(%s->is_dynamic() || %s->total() > %s->limit()){ ?>',$param,$param,$param);
			}
			$func .= sprintf('<?php try{ ?><?php if(%s instanceof \\ebi\\Paginator){ ?><ul class="pagination justify-content-center">',$param);
			if(isset($navi['prev'])){
				$func .= sprintf('<?php if(%s->is_prev()){ ?><li class="page-item prev"><a class="page-link" href="%s{%s.query_prev()}" rel="prev"><?php }else{ ?><li class="page-item prev disabled"><a class="page-link"><?php } ?>&laquo;</a></li>',$param,$href,$param);
			}
			if(isset($navi['first'])){
				$func .= sprintf('<?php if(!%s->is_dynamic() && %s->is_first(%d)){ ?><li page-item><a class="page-link" href="%s{%s.query(%s.first())}">{%s.first()}</a></li><li class="page-item disabled"><a class="page-link">...</a></li><?php } ?>',$param,$param,$counter,$href,$param,$param,$param);
			}
			if(isset($navi['counter'])){
				$func .= sprintf('<?php if(!%s->is_dynamic()){ ?>',$param)
					.sprintf('<?php if(%s->total() == 0){ ?>',$param)
						.sprintf('<li class="page-item active"><a class="page-link">1</a></li>')
					.'<?php }else{ ?>'
						.sprintf('<?php for(%s=%s->which_first(%d);%s<=%s->which_last(%d);%s++){ ?>',$counter_var,$param,$counter,$counter_var,$param,$counter,$counter_var)
							.sprintf('<?php if(%s == %s->current()){ ?>',$counter_var,$param)
								.sprintf('<li class="page-item active"><a class="page-link">{%s}</a></li>',$counter_var)
							.'<?php }else{ ?>'
								.sprintf('<li class="page-item"><a class="page-link" href="%s{%s.query(%s)}">{%s}</a></li>',$href,$param,$counter_var,$counter_var)
							.'<?php } ?>'
						.'<?php } ?>'
					.'<?php } ?>'
				.'<?php } ?>';
			}
			if(isset($navi['last'])){
				$func .= sprintf('<?php if(!%s->is_dynamic() && %s->is_last(%d)){ ?><li class="page-item disabled"><a class="page-link">...</a></li><li class="page-item"><a class="page-link" href="%s{%s.query(%s.last())}">{%s.last()}</a></li><?php } ?>',$param,$param,$counter,$href,$param,$param,$param);
			}
			if(isset($navi['next'])){
				$func .= sprintf('<?php if(%s->is_next()){ ?><li class="page-item next"><a class="page-link" href="%s{%s.query_next()}" rel="next"><?php }else{ ?><li class="page-item next disabled"><a class="page-link"><?php } ?>&raquo;</a></li>',$param,$href,$param);
			}
			$func .= "<?php } ?><?php }catch(\\Exception \$e){} ?></ul>";
			if($lt == 'false'){
				$func .= sprintf('<?php } ?>',$param);
			}
			return $func;
		});
	}
	
	private function form_variable_name($name){
		$m = [];
		return (strpos($name,'[') && preg_match("/^(.+)\[([^\"\']+)\]$/",$name,$m)) ?
			'{$'.$m[1].'["'.$m[2].'"]'.'}' : 
			'{$'.$name.'}';
	}
	private function is_reference($tag){
		$bool = ($tag->in_attr('rt:ref') === 'true');
		$tag->rm_attr('rt:ref');
		return $bool;
	}
	private function no_exception_str($value){
		return '<?php $_nes_=1; ?>'.$value.'<?php $_nes_=null; ?>';
	}
}
