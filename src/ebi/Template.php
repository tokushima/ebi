<?php
namespace ebi;
/**
 * テンプレートを処理する
 * @author tokushima
 * @var mixed{} $vars バインドされる変数
 * @var boolean $secure https://をhttp://に置換するか
 * @var string $put_block ブロックファイル
 * @var string $template_super 継承元テンプレート
 * @var string $media_url メディアファイルへのURLの基点
 */
class Template{
	use \ebi\Plugin,\ebi\TemplateVariable;
	
	private $file;
	private $selected_template;
	private $selected_src;

	private $secure = false;
	private $vars = [];
	private $put_block;
	private $template_super;
	private $media_url;

	public function __construct($media_url=null){
		if($media_url !== null) $this->media_url($media_url);
	}
	public function secure($bool=null){
		if($bool !== null) $this->secure = (boolean)$bool;
		return $this->secure;
	}
	public function vars($k=null,$v=null){
		if($k !== null) $this->vars[$k] = $v;
		return $this->vars;
	}
	public function in_vars($k,$d=null){
		return isset($this->vars[$k]) ? $this->vars[$k] : $d;
	}
	public function rm_vars($k=null){
		if($k === null){
			$this->vars = [];
		}else if(isset($this->vars[$k])){
			unset($this->vars[$k]);
		}
	}
	public function put_block($v=null){
		if($v !== null) $this->put_block = $v;
		return $this->put_block;
	}
	public function template_super($v=null){
		if($v !== null) $this->template_super = $v;
		return $this->template_super;
	}
	public function media_url($v=null){
		if($v !== null){
			$this->media_url = str_replace('\\','/',$v);
			if(!empty($this->media_url) && substr($this->media_url,-1) !== '/') $this->media_url = $this->media_url.'/';
		}
		return $this->media_url;
	}
	/**
	 * 配列からテンプレート変数に値をセットする
	 * @param array $array
	 */
	public function cp($array){
		if(is_array($array) || is_object($array)){
			foreach($array as $k => $v) $this->vars[$k] = $v;
		}else{
			throw new \ebi\exception\InvalidArgumentException('must be an of array');
		}
		return $this;
	}
	/**
	 * 出力する
	 * @param string $file
	 * @param string $template_name
	 */
	public function output($file,$template_name=null){
		print($this->read($file,$template_name));
		exit;
	}
	/**
	 * ファイルを読み込んで結果を返す
	 * @param string $file
	 * @param string $template_name
	 * @return string
	 */
	public function read($file,$template_name=null){
		if(!is_file($file) && strpos($file,'://') === false){
			throw new \ebi\exception\InvalidArgumentException($file.' not found');
		}
		$this->file = $file;
		$cname = md5($this->template_super.$this->put_block.$this->file.$this->selected_template);
		/**
		 * キャッシュのチェック
		 * @param string $cname キャッシュ名
		 * @return boolean
		 */
		if(static::call_class_plugin_funcs('has_template_cache',$cname) !== true){
			if(!empty($this->put_block)){
				$src = $this->read_src($this->put_block,$template_name);
				foreach(\ebi\Xml::anonymous($src)->find('rt:extends')  as $ext){
					$src = str_replace($ext->plain(),'',$src);
				}
				$src = sprintf('<rt:extends href="%s" />\n',$file).$src;
				$this->file = $this->put_block;
			}else{
				$src = $this->read_src($this->file,$template_name);
			}
			$src = $this->replace($src,$template_name);
			/**
			 * キャッシュにセットする
			 * @param string $cname キャッシュ名
			 * @param string $src 作成されたテンプレート
			 */
			static::call_class_plugin_funcs('set_template_cache',$cname,$src);
		}else{
			/**
			 * キャッシュから取得する
			 * @param string $cname キャッシュ名
			 * @return string
			 */
			$src = static::call_class_plugin_funcs('get_template_cache',$cname);
		}
		return $this->execute($src);
	}
	/**
	 * 文字列から結果を返す
	 * @param string $src
	 * @param string $template_name
	 * @return string
	 */
	public function get($src,$template_name=null){
		return $this->execute($this->replace($src,$template_name));
	}
	private function execute($src){
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
	private function replace($src,$template_name){
		$this->selected_template = $template_name;
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
		$src = $this->rtcomment($this->rtblock($src,$this->file));
		$this->selected_src = $src;
		/**
		 * 前処理
		 * @param string $src
		 * @return $src
		 */		
		foreach($this->get_object_plugin_funcs('before_template') as $o){
			$src = static::call_func($o,$src);
		}
		$src = $this->rtif($this->rtloop($this->html_form($this->html_list($src))));
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
		ob_start();
			if(is_array($this->vars) && !empty($this->vars)){
				extract($this->vars);
			}
			$rtn = eval('?>'.$_src_);
		$_eval_src_ = ob_get_clean();

		if($rtn === false){
			$error_last = error_get_last();

			if(isset($error_last['message']) && $error_last['message'] == 'parse error'){
				$line = $error_last['line'];
				$lines = explode("\n",$_src_);
				$plrp = substr_count(implode("\n",array_slice($lines,0,$line)),"<?php 'PLRP'; ?>\n");
				$plain = explode("\n",$this->selected_src);
				\ebi\Log::error('Parse error: line '.($line-$plrp).': '.PHP_EOL.'[complie] '.trim($lines[$line-1]).PHP_EOL.'[Source] '.trim($plain[$line-1-$plrp]));
			}
		}
		$_src_ = $this->selected_src = null;
		return $_eval_src_;
	}
	private function replace_xtag($src){
		if(preg_match_all("/<\?(?!php[\s\n])[\w]+ .*?\?>/s",$src,$null)){
			foreach($null[0] as $value){
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
		if(preg_match_all("/<([^<\n]+?[\s])(src|href|background)[\s]*=[\s]*([\"\'])([^\\3\n]+?)\\3[^>]*?>/i",$src,$match)){
			foreach($match[2] as $k => $p){
				list($url) = explode('?',$match[4][$k]);				
				if(strpos($url,'$') === false){
					$t = null;
					if(strtolower($p) === 'href'){
						list($t) = (preg_split("/[\s]/",strtolower($match[1][$k])));
					}
					$src = $this->replace_parse_url($src,(($this->secure && $t !== 'a') ? $secure_base : $media),$match[0][$k],$match[4][$k]);
				}
			}
		}
		if(preg_match_all("/[^:]:[\040]*url\(([^\\$\n]+?)\)/",$src,$match)){
			if($this->secure) $media = $secure_base;
			foreach(array_keys($match[1]) as $key){
				$src = $this->replace_parse_url($src,$media,$match[0][$key],$match[1][$key]);
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
	private function read_src($filename,$name=null){
		if(preg_match('/^(.*)#(.+)$/',$filename,$m)){
			list($filename,$name) = [$m[1],$m[2]];
		}
		$src = file_get_contents($filename);
		$src = (preg_match('/^http[s]*\:\/\//',$filename)) ? $this->parse_url($src,dirname($filename).'/') : $src;
		
		foreach(\ebi\Xml::anonymous($this->rtcomment($src))->find('rt:template') as $tag){
			if(empty($name) || $tag->in_attr('name') == $name){
				return $tag->value();
			}
		}
		return $src;
	}
	private function rtblock($src,$filename){
		if(strpos($src,'rt:block') !== false || strpos($src,'rt:extends') !== false){
			$base_filename = $filename;
			$blocks = $paths = $blockvars = [];
			try{
				while(true){
					$e = \ebi\Xml::extract($this->rtcomment($src),'rt:extends');
					$href = $e->in_attr('href');
					if(substr($href,0,1) == '#') $href = $filename.$href;
					$href = \ebi\Util::path_absolute(str_replace("\\",'/',dirname($filename)).'/',$href);
					if(empty($href) || !is_file(preg_replace('/^(.+)#.*$/','\\1',$href))){
						throw new \ebi\exception\InvalidTemplateException('href not found '.$filename);
					}
					if($filename === $href){
						throw new \ebi\exception\InvalidTemplateException('Infinite Recursion Error'.$filename);
					}
					$readxml = \ebi\Xml::anonymous($this->rtcomment($src));
					foreach($readxml->find('rt:block') as $b){
						$n = $b->in_attr('name');
						if(!empty($n) && !array_key_exists($n,$blocks)){
							$blocks[$n] = $b->value();
							$paths[$n] = $filename;
						}
					}
					foreach($readxml->find('rt:blockvar') as $b){
						if(!isset($blockvars[$b->in_attr('name')])){
							$blockvars[$b->in_attr('name')] = $b->value();
						}
					}
					$src = $this->replace_xtag($this->read_src($href));
					$filename = preg_replace('/^(.+)#.*$/','\\1',$href);
				}
			}catch(\ebi\exception\NotFoundException $e){
			}
			/**
			 * ブロック処理前の処理
			 * @param string $src
			 * @return $src
			 */
			foreach($this->get_object_plugin_funcs('before_block_template') as $o){
				$src = static::call_func($o,$src);
			}
			if(empty($blocks)){
				foreach(\ebi\Xml::anonymous($src)->find('rt:block') as $b){
					$src = str_replace($b->plain(),$b->value(),$src);
				}
			}else{
				if(!empty($this->template_super)){
					$src = $this->read_src(\ebi\Util::path_absolute(str_replace("\\",'/',dirname($base_filename).'/'),$this->template_super));
				}				
				try{
					while(true){
						$b = \ebi\Xml::extract($src,'rt:block');
						$n = $b->in_attr('name');
						$src = str_replace($b->plain(),(array_key_exists($n,$blocks) ? $blocks[$n] : $b->value()),$src);
					}
				}catch(\ebi\exception\NotFoundException $e){
				}
			}
			$this->file = $filename;
			foreach($blockvars as $k => $v){
				$this->vars($k,$v);
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
				
				$src = $this->php_exception_catch(str_replace(
					$tag->plain(),
					sprintf('<?php '
								.' %s=%s; '
								.' %s = 0; '
								.' foreach(%s as %s => %s){'
									.' %s++; '
							.' ?>'
									.'%s'
							.'<?php '
								.' } '
							.' ?>'
							,$varname,$param
							,$counter
							,$varname,$key,$var
								,$counter
							,$value
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
				$change = false;
				$uid = uniqid();

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
					$obj->rm_attr('rt:param','rt:key','rt:var');
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
									((preg_match("/^\{\$(.+)\}$/",$originalName,$match)) ?
											'{$$'.$match[1].'}' :
											'{$'.$originalName.'}'))));
						}
						$change = true;
					}
				}else if($tagname == 'textarea'){
					if($this->is_reference($obj)){
						$obj->value($this->no_exception_str(
							sprintf('{$_t_.htmlencode(%s)}',((preg_match("/^{\$(.+)}$/",$originalName,$match)) ? 
								'{$$'.$match[1].'}' : 
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
		if(preg_match_all('/<(table|ul|ol)\s[^>]*rt\:/i',$src,$m,PREG_OFFSET_CAPTURE)){
			$tags = [];
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
	private function form_variable_name($name){
		return (strpos($name,'[') && preg_match("/^(.+)\[([^\"\']+)\]$/",$name,$match)) ?
			'{$'.$match[1].'["'.$match[2].'"]'.'}' : '{$'.$name.'}';
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
