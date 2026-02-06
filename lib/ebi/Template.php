<?php
namespace ebi;

class Template{
	use \ebi\TemplateVariable;

	private bool $secure = false;
	private array $vars = [];
	private ?string $media_url = null;

	/**
	 * @var string|array $base_dir
	 */
	private $base_dir = null;

	private static array $escape_pairs = [
		'from' => ["\\\\", "\\\"", "\\'"],
		'to' => ['__ESC_DESC__', '__ESC_DQ__', '__ESC_SQ__']
	];

	private static array $php_pairs = [
		'from' => [' ?>', '<?php ', '->'],
		'to' => ['__PHP_TAG_END__', '__PHP_TAG_START__', '__PHP_ARROW__']
	];

	/**
	 * メディアURLをhttpsにする
	 */
	public function secure(bool $bool): self{
		$this->secure = $bool;
		return $this;
	}

	/**
	 * 変数をバインドする
	 * @param mixed $v
	 */
	public function vars(string $k, $v): self{
		$this->vars[$k] = $v;
		return $this;
	}

	/**
	 * メディアURLを設定する
	 */
	public function media_url(string $v): self{
		$this->media_url = \ebi\Util::path_slash($v, null, true);
		return $this;
	}

	/**
	 * ファイルを読み込んで結果を返す
	 * @param mixed $base_dir string|array
	 */
	public function read(string $filename, $base_dir=null): string{
		$this->base_dir = $base_dir ?? dirname(realpath($filename));
		$src = $this->read_src($filename);

		return $this->get($src, $base_dir);
	}

	/**
	 * 文字列から結果を返す
	 * @param mixed $base_dir string|array
	 */
	public function get(string $src, $base_dir=null): string{
		$this->base_dir = $base_dir ?? getcwd();

		$src = $this->replace($src);
		$src = $this->exec($src);

		if(strpos($src, 'rt:ref') !== false){
			$src = str_replace(['#PS#', '#PE#'], ['<?', '?>'], $this->html_form($src));
			$src = $this->exec($this->parse_print_variable($this->html_input($src)));
		}
		return $src;
	}

	/**
	 * 出力する
	 * @param mixed $base_dir string|array
	 */
	public function output(string $file, $base_dir=null): void{
		print($this->read($file, $base_dir));
		exit;
	}

	private function replace(string $src): string{
		$src = preg_replace("/([\w])\->/", "\\1__PHP_ARROW__", $src);
		$src = str_replace(self::$escape_pairs['from'], self::$escape_pairs['to'], $src);
		$src = $this->replace_xtag($src);
		$src = $this->rtcomment($this->rtinclude($this->rtblock($src)));
		$src = $this->rtinvalid($src);
		$src = $this->html_list($src);
		$src = $this->html_form($src);
		$src = $this->rtloop($src);
		$src = $this->rtif($src);
		$src = $this->rtpaginator($src);
		$src = str_replace('__PHP_ARROW__', '->', $src);
		$src = $this->parse_print_variable($src);
		$src = str_replace(self::$php_pairs['from'], self::$php_pairs['to'], $src);

		$keys = $tags = [];
		if($this->html_script_search($src, $keys, $tags)){
			$src = str_replace($tags, $keys, $src);
			$src = $this->parse_url($src, $this->media_url);
			$src = str_replace($keys, $tags, $src);
		}else{
			$src = $this->parse_url($src, $this->media_url);
		}

		$src = str_replace(self::$php_pairs['to'], self::$php_pairs['from'], $src);
		$src = str_replace(self::$escape_pairs['to'], self::$escape_pairs['from'], $src);
		return $src;
	}

	private function exec(string $_src_): string{
		foreach($this->default_vars() as $k => $v){
			$this->vars($k, $v);
		}

		try{
			ob_start();
			if(!empty($this->vars)){
				extract($this->vars);
			}
			eval('?>' . $_src_);
			$_eval_src_ = ob_get_clean();
		}catch(\ParseError $e){
			ob_clean();
			throw new \ebi\exception\InvalidTemplateException($e->getMessage());
		}
		return $_eval_src_;
	}

	private function replace_xtag(string $src): string{
		return preg_replace_callback(
			"/<\?(?!php[\s\n])[\w]+ .*?\?>/s",
			fn($m) => '#PS#' . substr($m[0], 2, -2) . '#PE#',
			$src
		);
	}

	private function parse_url(string $src, ?string $media): string{
		if(!empty($media) && substr($media, -1) !== '/'){
			$media .= '/';
		}
		$secure_base = ($this->secure) ? str_replace('http://', 'https://', $media ?? '') : null;

		// src, href, background属性のURL変換
		$src = preg_replace_callback(
			"/<([^<\n]+?[\s])(src|href|background)[\s]*=[\s]*([\"\'])([^\\3\n]+?)\\3[^>]*?>/i",
			function($m) use ($media, $secure_base){
				[$url] = explode('?', $m[4]);
				if(strpos($url, '$') !== false){
					return $m[0];
				}
				$t = null;
				if(strtolower($m[2]) === 'href'){
					[$t] = preg_split("/[\s]/", strtolower($m[1]));
				}
				$base = ($this->secure && $t !== 'a') ? $secure_base : $media;
				return $this->replace_parse_url_inline($m[0], $base, $m[4]);
			},
			$src
		);

		// CSS url()の変換
		$src = preg_replace_callback(
			"/([^:]):[\040]*url\(([^\\$\n]+?)\)/",
			function($m) use ($media, $secure_base){
				$base = $this->secure ? $secure_base : $media;
				return $m[1] . ':url(' . $this->resolve_url($base, trim($m[2], "\"'")) . ')';
			},
			$src
		);

		return $src;
	}

	private function replace_parse_url_inline(string $tag, ?string $base, string $url): string{
		if(preg_match("/(^\/\/)|(^[\w]+:\/\/)|(^__PHP_TAG_START)|(^\w+:)|(^[#\?])/", $url)){
			return $tag;
		}
		return str_replace($url, \ebi\Util::path_absolute($base, $url), $tag);
	}

	private function resolve_url(?string $base, string $url): string{
		if(preg_match("/(^\/\/)|(^[\w]+:\/\/)|(^__PHP_TAG_START)|(^\w+:)|(^[#\?])/", $url)){
			return $url;
		}
		return \ebi\Util::path_absolute($base, $url);
	}

	private function read_src(string $filename): string{
		if(preg_match('/^http[s]*\:\/\//', $filename)){
			return $this->parse_url(file_get_contents($filename), dirname($filename));
		}
		$dirs = is_array($this->base_dir) ? $this->base_dir : [$this->base_dir];
		foreach($dirs as $d){
			$f = \ebi\Util::path_absolute($d, $filename);
			if(is_file($f)){
				return file_get_contents($f);
			}
		}
		throw new \ebi\exception\AccessDeniedException(sprintf('permission denied `%s`', $filename));
	}

	private function rtinclude(string $src): string{
		return \ebi\Xml::find_replace_all($src, 'rt:include', function($tag){
			return $this->read_src($tag->in_attr('href'));
		});
	}

	private function rtblock(string $src): string{
		if(strpos($src, 'rt:block') === false && strpos($src, 'rt:extends') === false){
			return $src;
		}

		$blocks = [];

		try{
			while(true){
				$extends = \ebi\Xml::extract($this->rtcomment($src), 'rt:extends');
				$readxml = \ebi\Xml::anonymous($this->rtcomment($src));

				foreach($readxml->find('rt:block') as $b){
					$n = $b->in_attr('name');
					if(!empty($n) && !array_key_exists($n, $blocks)){
						$blocks[$n] = (string)$b->value();
					}
				}
				$src = $this->replace_xtag($this->read_src($extends->in_attr('href')));
			}
		}catch(\ebi\exception\NotFoundException $e){
		}

		return \ebi\Xml::find_replace_all($src, 'rt:block', function($b) use ($blocks){
			$n = $b->in_attr('name');
			return array_key_exists($n, $blocks) ? $blocks[$n] : (string)$b->value();
		});
	}

	private function rtcomment(string $src): string{
		return \ebi\Xml::find_replace_all($src, 'rt:comment', fn($tag) => '');
	}

	private function rtloop(string $src): string{
		return \ebi\Xml::find_replace_all($src, 'rt:loop', function($tag){
			$tag->escape(false);
			$value = $tag->value();

			// ネストされたrt:loopを再帰処理
			if(strpos($value, 'rt:loop') !== false){
				$value = $this->rtloop($value);
			}

			$uniq = uniqid('');
			$param = ($tag->is_attr('param')) ? $this->variable_string($this->parse_plain_variable($tag->in_attr('param'))) : null;
			$varname = '$_' . $uniq;
			$var = '$' . $tag->in_attr('var', '_v_' . $uniq);
			$key = '$' . $tag->in_attr('key', '_k_' . $uniq);
			$counter = '$' . $tag->in_attr('counter', '_c_' . $uniq);
			$limitname = '$_l_' . $uniq;
			$limit = $tag->in_attr('limit', '0');

			return $this->php_exception_catch(sprintf(
				'<?php %s = %s; %s = 0; %s = %s; foreach(%s as %s => %s){ %s++; ?>%s<?php if(%s > 0 && %s <= %s){ break; } } ?>',
				$varname, $param,
				$counter,
				$limitname, $limit,
				$varname, $key, $var,
				$counter,
				$value,
				$limitname, $limitname, $counter
			));
		});
	}

	private function rtif(string $src): string{
		return \ebi\Xml::find_replace_all($src, 'rt:if', function($tag){
			$tag->escape(false);

			if(!$tag->is_attr('param')){
				throw new \ebi\exception\InvalidTemplateException('if');
			}
			$uniq = uniqid('$I');
			$arg1 = $this->variable_string($this->parse_plain_variable($tag->in_attr('param')));

			return $this->php_exception_catch(
				sprintf('<?php try{ %s=%s; }catch(\Exception $e){ %s=null; } ?><?php if(%s){ ?>',
					$uniq, $arg1, $uniq, $uniq
				) .
				preg_replace('/<rt\:else[\s]*.*?>/i', '<?php }else{ ?>', $tag->value() ?? '') .
				'<?php } ?>'
			);
		});
	}

	private function html_script_search(string $src, array &$keys, array &$tags): bool{
		$keys = $tags = [];
		$uniq = uniqid('uniq');
		$i = 0;

		foreach(\ebi\Xml::anonymous($src)->find('script') as $obj){
			if(!$obj->is_attr('src')){
				$keys[] = '__' . $uniq . ($i++) . '__';
				$tags[] = $obj->plain();
			}
		}
		return ($i > 0);
	}

	private function html_form(string $src): string{
		foreach(\ebi\Xml::anonymous($src)->find('form') as $obj){
			if($obj->in_attr('rt:aref') === 'true'){
				$obj->rm_attr('rt:aref');
				$obj->attr('rt:ref', 'true');

				if($obj->is_attr('rt:param')){
					$obj->attr('rt:param', str_replace('{$', '{#', $obj->in_attr('rt:param')));
				}
				$src = str_replace($obj->plain(), $obj->get(), $src);
			}else if($this->is_reference($obj)){
				$obj->escape(false);

				if($obj->is_attr('rt:param')){
					$param = $this->variable_string($this->parse_plain_variable(str_replace('{#', '{$', $obj->in_attr('rt:param'))));
					$uniq = uniqid('');
					$var = '$__form_var__' . $uniq;
					$k = '$__form_k__' . $uniq;
					$v = '$__form_v__' . $uniq;
					$tag = $this->php_exception_catch(sprintf(
						'<?php %s=%s; if((is_array(%s) || (is_object(%s) && %s instanceof \Traversable))){ foreach(%s as %s => %s){ if(preg_match(\'/^[a-zA-Z0-9_]+$/\',%s) && !isset($%s)){ $%s = %s; } } } ?>',
						$var, $param,
						$var, $var, $var,
						$var, $k, $v,
						$k, $k, $k, $v
					)) . PHP_EOL;
					$obj->rm_attr('rt:param');
					$obj->value($tag . $obj->value());
				}
				foreach($obj->find('input|select|textarea') as $tag){
					if(!$tag->is_attr('rt:ref') && ($tag->is_attr('name') || $tag->is_attr('id'))){
						$type = strtolower($tag->in_attr('type', 'text'));
						if($type !== 'button' && $type !== 'submit'){
							if($type === 'file'){
								$obj->attr('enctype', 'multipart/form-data');
								$obj->attr('method', 'post');
							}else{
								$tag->attr('rt:ref', 'true');
								$obj->value(str_replace($tag->plain(), $tag->get(), $obj->value()));
							}
						}
					}
				}
				$src = str_replace($obj->plain(), $obj->get(), $src);
			}
		}
		return $this->html_input($src);
	}

	private function html_input(string $src): string{
		foreach(\ebi\Xml::anonymous($src)->find('input|textarea|select') as $obj){
			$originalName = $obj->in_attr('name', $obj->in_attr('id', ''));
			if($originalName === ''){
				continue;
			}

			$obj->escape(false);
			$type = strtolower($obj->in_attr('type', 'text'));
			$name = $this->parse_plain_variable($this->form_variable_name($originalName));
			$tagname = strtolower($obj->name());
			$change = $obj->is_attr('rt:ref');
			$uid = uniqid();

			if(substr($originalName, -2) !== '[]'){
				if($type === 'checkbox'){
					if($obj->in_attr('rt:multiple', 'true') === 'true'){
						$obj->attr('name', $originalName . '[]');
					}
					$obj->rm_attr('rt:multiple');
					$change = true;
				}else if($obj->is_attr('multiple') || $obj->in_attr('multiple') === 'multiple'){
					$obj->attr('name', $originalName . '[]');
					$obj->rm_attr('multiple');
					$obj->attr('multiple', 'multiple');
					$change = true;
				}
			}else if($obj->in_attr('name') !== $originalName){
				$obj->attr('name', $originalName);
				$change = true;
			}

			if($obj->is_attr('rt:param') && $tagname === 'select'){
				$value = $obj->value();
				$varName = $obj->in_attr('rt:var', 'loop_var' . $uid);
				$keyName = $obj->in_attr('rt:key', 'loop_key' . $uid);
				$value = sprintf(
					'<rt:loop param="%s" var="%s" key="%s">%s</rt:loop>',
					$obj->in_attr('rt:param'),
					$varName,
					$keyName,
					(trim($value ?? '') === '') ? sprintf('<option value="{$%s}">{$%s}</option>', $keyName, $varName) : $value
				);
				$obj->value($this->rtloop($value));
				if($obj->is_attr('rt:null')){
					$obj->value('<option value="">' . $obj->in_attr('rt:null') . '</option>' . $obj->value());
				}
				$obj->rm_attr('rt:param', 'rt:key', 'rt:var', 'rt:null');
				$change = true;
			}

			if($tagname === 'input'){
				if($this->is_reference($obj)){
					if($type === 'checkbox' || $type === 'radio'){
						$value = $this->parse_plain_variable($obj->in_attr('value', 'true'));
						$value = ($value[0] !== '$') ? sprintf("'%s'", $value) : $value;
						$obj->rm_attr('checked');
						$obj->plain_attr($this->check_selected($name, $value, 'checked'));
					}else{
						$m = [];
						$obj->attr('value', $this->no_exception_str(sprintf('{$_t_.htmlencode(%s)}',
							(preg_match("/^\{\$(.+)\}$/", $originalName, $m)) ?
								'{$$' . $m[1] . '}' :
								'{$' . $originalName . '}')));
					}
					$change = true;
				}
			}else if($tagname === 'textarea'){
				if($this->is_reference($obj)){
					$m = [];
					$obj->value($this->no_exception_str(
						sprintf('{$_t_.htmlencode(%s)}',
							(preg_match("/^{\$(.+)}$/", $originalName, $m)) ?
								'{$$' . $m[1] . '}' :
								'{$' . $originalName . '}'
						)
					));
					$obj->close_empty(false);
					$change = true;
				}
			}else if($tagname === 'select'){
				if($this->is_reference($obj) || $obj->is_attr('value')){
					$select = $obj->value();
					$name = $this->parse_plain_variable($obj->in_attr('value', $name));
					$obj->rm_attr('value');

					foreach($obj->find('option') as $option){
						$option->escape(false);
						$value = $this->parse_plain_variable($option->in_attr('value'));

						if(empty($value) || $value[0] !== '$'){
							$value = sprintf("'%s'", $value);
						}
						$option->rm_attr('selected');
						$option->plain_attr($this->check_selected($name, $value, 'selected'));
						$select = str_replace($option->plain(), $option->get(), $select);
					}
					$obj->value($select);
					$obj->close_empty(false);
					$change = true;
				}
			}
			if($change){
				$src = str_replace($obj->plain(), $obj->get(), $src);
			}
		}
		return $src;
	}

	private function check_selected(string $name, string $value, string $selected): string{
		$isset = (strpos($name, '->') === false) ? 'isset(' . $name . ') && ' : '';
		return sprintf(
			'<?php if(%s(%s === %s || (!is_array(%s) && ctype_digit((string)%s) && (string)%s === (string)%s) || ((%s === "true" || %s === "false") ? (%s === (%s == "true")) : false) || in_array(%s,((is_array(%s)) ? %s : (is_null(%s) ? [] : [%s])),true))){ print(" %s=\"%s\""); } ?>',
			$isset,
			$name, $value,
			$name, $name, $name, $value,
			$value, $value, $name, $value,
			$value, $name, $name, $name, $name,
			$selected, $selected
		);
	}

	private function html_list(string $src): string{
		$m = [];
		if(!preg_match_all('/<(table|ul|ol)\s[^>]*rt\:/i', $src, $m, PREG_OFFSET_CAPTURE)){
			return $src;
		}

		$tags = [];
		foreach($m[1] as $v){
			try{
				$tags[] = \ebi\Xml::extract(substr($src, $v[1] - 1), $v[0]);
			}catch(\ebi\exception\NotFoundException $e){
			}
		}

		foreach($tags as $obj){
			$obj->escape(false);
			$name = strtolower($obj->name());
			$param = $obj->in_attr('rt:param');
			$loopStart = sprintf(
				'<rt:loop param="%s" var="%s" counter="%s" key="%s">',
				$param,
				$obj->in_attr('rt:var', 'loop_var'),
				$obj->in_attr('rt:counter', 'loop_counter'),
				$obj->in_attr('rt:key', 'loop_key')
			);
			$rawvalue = $obj->value();

			if($name === 'table'){
				try{
					$t = \ebi\Xml::extract($rawvalue, 'tbody');
					$t->escape(false);
					$t->value($loopStart . $t->value() . '</rt:loop>');
					$value = str_replace($t->plain(), $t->get(), $rawvalue);
				}catch(\ebi\exception\NotFoundException $e){
					$value = $loopStart . $rawvalue . '</rt:loop>';
				}
			}else{
				$value = $loopStart . $rawvalue . '</rt:loop>';
			}

			$obj->value($this->html_list($value));
			$obj->rm_attr('rt:param', 'rt:key', 'rt:var', 'rt:counter');
			$src = str_replace($obj->plain(), $obj->get(), $src);
		}
		return $src;
	}

	private function rtpaginator(string $src): string{
		return \ebi\Xml::find_replace($src, 'rt:paginator', function($xml){
			$param = $this->variable_string($this->parse_plain_variable($xml->in_attr('param', 'paginator')));
			$counter = $xml->in_attr('counter', 10);
			$href = $xml->in_attr('href', '?');
			$cv = '$__c__' . uniqid('');

			$html = sprintf('<?php if(%s instanceof \\ebi\\Paginator){ ?><ul class="pagination justify-content-center">', $param);
			$html .= sprintf('<?php if(%s->is_prev()){ ?><li class="page-item prev"><a class="page-link" href="%s{%s.query_prev()}" rel="prev"><?php }else{ ?><li class="page-item prev disabled"><a class="page-link"><?php } ?>&laquo;</a></li>', $param, $href, $param);
			$html .= sprintf('<?php if(%s->is_first(%d)){ ?><li page-item><a class="page-link" href="%s{%s.query(%s.first())}">{%s.first()}</a></li><li class="page-item disabled"><a class="page-link">...</a></li><?php } ?>', $param, $counter, $href, $param, $param, $param);
			$html .= sprintf('<?php if(%s->total() == 0){ ?><li class="page-item active"><a class="page-link">1</a></li><?php }else{ ?>', $param);
			$html .= sprintf('<?php for(%s=%s->which_first(%d);%s<=%s->which_last(%d);%s++){ ?>', $cv, $param, $counter, $cv, $param, $counter, $cv);
			$html .= sprintf('<?php if(%s == %s->current()){ ?><li class="page-item active"><a class="page-link">{%s}</a></li><?php }else{ ?>', $cv, $param, $cv);
			$html .= sprintf('<li class="page-item"><a class="page-link" href="%s{%s.query(%s)}">{%s}</a></li><?php } ?><?php } ?><?php } ?>', $href, $param, $cv, $cv);
			$html .= sprintf('<?php if(%s->is_last(%d)){ ?><li class="page-item disabled"><a class="page-link">...</a></li><li class="page-item"><a class="page-link" href="%s{%s.query(%s.last())}">{%s.last()}</a></li><?php } ?>', $param, $counter, $href, $param, $param, $param);
			$html .= sprintf('<?php if(%s->is_next()){ ?><li class="page-item next"><a class="page-link" href="%s{%s.query_next()}" rel="next"><?php }else{ ?><li class="page-item next disabled"><a class="page-link"><?php } ?>&raquo;</a></li>', $param, $href, $param);
			$html .= '</ul><?php } ?>';

			return sprintf('<?php try{ ?>%s<?php }catch(\Exception $e){} ?>', $html);
		});
	}

	private function rtinvalid(string $src): string{
		return \ebi\Xml::find_replace_all($src, 'rt:invalid', function($xml){
			$group = $xml->in_attr('group');
			$type = $xml->in_attr('type');
			$var = $xml->in_attr('var', 'rtinvalid_var' . uniqid(''));

			$group = (!isset($group[0]) || $group[0] !== '$') ? '"' . $group . '"' : $group;
			$type = (!isset($type[0]) || $type[0] !== '$') ? '"' . $type . '"' : $type;

			$value = $xml->value();
			if(empty($value)){
				$varnm = 'rtinvalid_varnm' . uniqid('');
				$value = sprintf(
					'<div class="%s"><ul><rt:loop param="%s" var="%s"><li><rt:if param="{$t.has($%s.getMessage())}">{$%s.getMessage()}<rt:else />{$t.get_class($%s)}</rt:if></li></rt:loop></ul></div>',
					$xml->in_attr('class', 'alert alert-danger'),
					$var, $varnm, $varnm, $varnm, $varnm
				);
			}
			return sprintf(
				'<?php if(\ebi\FlowInvalid::has(%s,%s)){ ?><?php $%s = \ebi\FlowInvalid::get(%s,%s); ?>%s<?php } ?>',
				$group, $type, $var, $group, $type,
				preg_replace("/<rt\:else[\s]*.*?>/i", "<?php }else{ ?>", $value)
			);
		});
	}

	private function form_variable_name(string $name): string{
		$m = [];
		return (strpos($name, '[') !== false && preg_match("/^(.+)\[([^\"\']+)\]$/", $name, $m)) ?
			'{$' . $m[1] . '["' . $m[2] . '"]' . '}' :
			'{$' . $name . '}';
	}

	private function is_reference(\ebi\Xml $tag): bool{
		$bool = ($tag->in_attr('rt:ref') === 'true');
		$tag->rm_attr('rt:ref');
		return $bool;
	}

	private function no_exception_str(string $value): string{
		return '<?php $_nes_=1; ?>' . $value . '<?php $_nes_=null; ?>';
	}
}
