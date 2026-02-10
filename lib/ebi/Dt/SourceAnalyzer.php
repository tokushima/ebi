<?php
namespace ebi\Dt;
/**
 * ソースコード解析
 * クラス・メソッドのドキュメント、設定、メールテンプレートを抽出
 */
class SourceAnalyzer{
	private static function get_reflection_source(\ReflectionClass $r): string{
		return implode(array_slice(
			file($r->getFileName()),
			$r->getStartLine(),
			($r->getEndLine()-$r->getStartLine()-1)
		));
	}

	private static function get_method_document(\ReflectionMethod $method): string{
		$method_document = $method->getDocComment();

		if($method_document === false){
			$p = $method->getDeclaringClass()->getParentClass();

			while($p !== false){
				try{
					$method_document = $p->getMethod($method->getName())->getDocComment();

					if($method_document !== false){
						break;
					}
					$p = $p->getParentClass();
				}catch(\ReflectionException $e){
					break;
				}
			}
		}
		return self::trim_doc($method_document);
	}

	/**
	 * クラスの情報を取得
	 */
	public static function class_info(string $class): DocInfo{
		$info = new DocInfo();
		$r = new \ReflectionClass(self::get_class_name($class));

		if($r->getFilename() === false || !is_file($r->getFileName())){
			throw new \ebi\exception\InvalidArgumentException('`'.$class.'` file not found.');
		}
		$src = self::get_reflection_source($r);
		$document = self::trim_doc($r->getDocComment());

		$info->name($r->getName());
		$info->document(trim(preg_replace('/\n*@.+/','',PHP_EOL.$document)));

		$info->set_opt('filename',$r->getFileName());
		$info->set_opt('extends',(($r->getParentClass() === false) ? null : $r->getParentClass()->getName()));
		$info->set_opt('abstract',$r->isAbstract());
		$info->set_opt('see_list',self::find_see($document));

		// サーバー間通信エンドポイント（クラスレベル: DocBlockまたはAttribute）
		if(preg_match('/@s2s/',$document)){
			$info->set_opt('s2s',true);
		}else{
			$s2s_attr = \ebi\AttributeReader::get_class($r->getName(), 's2s');
			if($s2s_attr !== null){
				$info->set_opt('s2s',true);
			}
		}

		self::find_merge_deprecate($info,$document);

		$methods = $static_methods = [];
		foreach($r->getMethods() as $method){
			if(substr($method->getName(),0,1) != '_' && $method->isPublic()){
				$ignore = ['getIterator'];

				if(!in_array($method->getName(),$ignore)){
					$bool = true;

					foreach([\ebi\Obj::class,\ebi\Dao::class,\ebi\flow\Request::class,\ebi\Request::class] as $ignore_class){
						if($r->getName() != $ignore_class && $method->getDeclaringClass()->getName() == $ignore_class){
							$bool = false;
						}
					}
					if($bool){
						$method_info = new DocInfo();
						$method_info->name($method->getName());

						$method_document = self::get_method_document($method);
						$method_document = self::find_merge_deprecate($method_info,$method_document);

						[$summary] = explode(PHP_EOL,trim(preg_replace('/@.+/','',$method_document)));

						$method_info->document($summary);

						if($method->isStatic()){
							$static_methods[] = $method_info;
						}else{
							$methods[] = $method_info;
						}
					}
				}
			}
		}
		$info->set_opt('static_methods',$static_methods);
		$info->set_opt('methods',$methods);

		$properties = [];
		$anon = \ebi\AttributeReader::get_class($info->name(),'var','summary');

		// traitのDocBlockからも@varアノテーションを取得
		foreach($r->getTraits() as $trait){
			$trait_anon = \ebi\AttributeReader::get_class($trait->getName(),'var','summary');
			foreach($trait_anon as $name => $val){
				if(!isset($anon[$name])){
					$anon[$name] = $val;
				}
			}
		}

		$is_obj = $r->isSubclassOf(\ebi\Obj::class);

		$get_type_format = function($arr){
			if(isset($arr['type'])){
				if(isset($arr['attr'])){
					return $arr['type'].($arr['attr'] == 'a' ? '[]' : '{}');
				}
				return $arr['type'];
			}
			return 'mixed';
		};

		foreach($r->getProperties() as $prop){
			if($prop->isPublic() || ($is_obj && $prop->isProtected())){
				$name = $prop->getName();

				if($name[0] != '_' && !$prop->isStatic()){
					// アノテーションから型を取得
					$type = $get_type_format($anon[$name] ?? 'mixed');

					// アノテーションで型が取れない場合、ReflectionPropertyから取得
					if($type === 'mixed' && ($ref_type = $prop->getType()) !== null){
						$type = $ref_type->getName();
					}

					$properties[$name] = new ParamInfo(
						$name,
						$type
					);
					$properties[$name]->summary(
						self::find_merge_deprecate(
							$properties[$name],
							$anon[$name]['summary'] ?? '',
							$info,
							true
						)
					);
					$properties[$name]->set_opt(
						'hash',
						($prop->isPublic() || !($anon[$name]['hash'] ?? true) === false)
					);

					if(!empty($anon[$name]['cond'])){
						$properties[$name]->set_opt('cond', $anon[$name]['cond']);
					}
				}
			}
		}

		// Daoの場合、命名規則に基づくプロパティ情報を補完
		if($is_obj && is_subclass_of($class, \ebi\Dao::class)){
			foreach($properties as $name => $prop){
				$type = $prop->type();

				if($name === 'id'){
					if(empty($type) || $type === 'mixed'){
						$prop->type('integer');
					}
					$prop->set_opt('primary', true);
					$prop->set_opt('auto', true);
				}else if(in_array($name, ['created_at', 'create_date', 'created'])){
					if(empty($type) || $type === 'mixed'){
						$prop->type('string');
					}
					$prop->set_opt('format', 'date-time');
					$prop->set_opt('auto_now_add', true);
				}else if(in_array($name, ['updated_at', 'update_date', 'modified'])){
					if(empty($type) || $type === 'mixed'){
						$prop->type('string');
					}
					$prop->set_opt('format', 'date-time');
					$prop->set_opt('auto_now', true);
				}else if($name === 'code'){
					if(empty($type) || $type === 'mixed'){
						$prop->type('string');
					}
					$prop->set_opt('auto_code_add', true);
				}
			}
		}

		$info->set_opt('properties',$properties);

		// Conf::get/gets呼び出しを抽出
		$config_list = [];
		foreach([
			"/Conf::gets\(([\"\'])(.+?)\\1/"=>'mixed[]',
			"/Conf::get\(([\"\'])(.+?)\\1/"=>'mixed',
			"/self::get_self_conf_get\(([\"\'])(.+?)\\1/"=>'mixed',
		] as $preg => $default_type){
			if(preg_match_all($preg,$src,$m,PREG_OFFSET_CAPTURE)){
				foreach($m[2] as $k => $v){
					if(!array_key_exists($v[0],$config_list) || !$config_list[$v[0]]->has_params()){
						$conf_info = DocInfo::parse($v[0],$src,$m[0][$k][1]);
						$conf_info->set_opt('def',\ebi\Conf::defined($r->getName().'@'.$v[0]));

						if(!$conf_info->has_params()){
							$conf_info->add_params(new ParamInfo('val',$default_type));
						}
						$config_list[$v[0]] = $conf_info;
					}
				}
			}
		}
		ksort($config_list);
		$info->set_opt('config_list',$config_list);

		return $info;
	}

	private static function get_class_name(string $class_name): string{
		$class_name = str_replace(['.','/'],['\\','\\'],$class_name);

		if(class_exists($class_name)){
			$r = new \ReflectionClass($class_name);
			$name = $r->getName();
			return ($name[0] !== '\\') ? '\\'.$name : $name;
		}
		return '';
	}

	private static function find_merge_params(DocInfo $info, array $parameters): void{
		$doc_params = $info->params();
		$info->rm_params();

		foreach($parameters as $param){
			$has = false;
			foreach($doc_params as $doc_param){
				if($doc_param->name() == $param->getName()){
					$info->add_params($doc_param);
					$has = true;
					break;
				}
			}
			if(!$has){
				$info->add_params(new ParamInfo($param->getName(),'mixed'));
			}
		}
	}

	private static function find_merge_deprecate(DocInfo|ParamInfo $info, string $summary, ?DocInfo $root_obj=null, bool $contains=false): string{
		if(preg_match('/'.($contains ? '' : '^').'@deprecated(.*)/m',$summary,$m)){
			$d = time();

			if(preg_match('/\d{4}[\-\/\.]*\d{1,2}[\-\/\.]*\d{1,2}/',$m[1],$mm)){
				$d = strtotime($mm[0]);
			}
			$info->set_opt('deprecated',$d);

			if(isset($root_obj) && (empty($root_obj->opt('first_deprecated_date')) || $root_obj->opt('first_deprecated_date') > $d)){
				$root_obj->set_opt('first_deprecated_date',$d);
			}
		}
		if(preg_match('/@deprecated_see\s+(\S+)/',$summary,$m)){
			$see = self::classify_see(trim($m[1]));
			if(!empty($see)){
				$info->set_opt('deprecated_see', $see);
			}
			if(empty($info->opt('deprecated'))){
				$info->set_opt('deprecated', time());
			}
		}
		return trim(preg_replace('/@.+/','',$summary));
	}

	private static function find_merge_request_context(DocInfo $info, string $document): void{
		$requests = ParamInfo::parse('request',$document);
		$contexts = ParamInfo::parse('context',$document);

		foreach([$requests,$contexts] as $v){
			foreach($v as $r){
				$r->summary(self::find_merge_deprecate($r,$r->summary(),$info,true));
			}
		}
		$info->set_opt('requests',$requests);
		$info->set_opt('contexts',$contexts);

		if(!$info->is_return() && $info->has_opt('contexts')){
			$info->return(new ParamInfo('return','mixed{}'));
		}
	}

	private static function find_throws(array $throws, string $doc, string $src): array{
		if(preg_match_all("/@throws\s+([^\s]+)(.*)/",$doc,$m)){
			foreach($m[1] as $k => $n){
				if(($class_name = self::get_class_name($n)) !== ''){
					$throws[$class_name] = isset($throws[$class_name])
						? [$class_name,$throws[$class_name][1].trim(PHP_EOL.$m[2][$k])]
						: [$class_name,$m[2][$k]];
				}
			}
		}
		if(preg_match_all("/throw new\s([\w\\\\]+)/",$src,$m)){
			foreach($m[1] as $n){
				if(($class_name = self::get_class_name($n)) !== '' && !isset($throws[$class_name])){
					$throws[$n] = [$n,''];
				}
			}
		}
		if(preg_match_all("/catch\s*\(\s*([\w\\\\]+)/",$src,$m)){
			foreach($m[1] as $n){
				if(($class_name = self::get_class_name($n)) !== ''){
					unset($throws[$class_name]);
				}
			}
		}
		return $throws;
	}

	private static function merge_find_throws(array $throws): array{
		$throw_param = [];

		foreach($throws as $n => $t){
			try{
				$ref = new \ReflectionClass($n);
				$doc = empty($t[1]) ? trim(preg_replace('/@.+/','',self::trim_doc($ref->getDocComment()))) : $t[1];
				$throw_param[$n] = new ParamInfo($ref->getName(),$ref->getName(),trim($doc));
			}catch(\ReflectionException){
			}
		}
		return $throw_param;
	}

	/**
	 * メールテンプレートでの使用箇所を検索
	 */
	public static function find_mail_doc(DocInfo $mail_info, string $src): bool{
		if(preg_match_all('/[^\w\/_]'.preg_quote($mail_info->name(),'/').'/',$src,$m,PREG_OFFSET_CAPTURE)){
			$doc = DocInfo::parse('',$src,$m[0][0][1]);

			if(empty($doc->document())){
				if(preg_match('/\/\*\*(((?!\/\*\*).)*@real\s'.preg_quote($mail_info->name(),'/').'((?!\/\*\*).)*?\*\/)/s',$src,$m)){
					$doc = DocInfo::parse('',$m[1]);
				}
			}
			$mail_info->set_opt('use',true);
			$mail_info->set_opt('description',$doc->document());

			foreach($doc->params() as $p){
				$mail_info->add_params($p);
			}

			$mail_info->add_params(new ParamInfo('t', '\ebi\FlowHelper','Helper'));
			$mail_info->add_params(new ParamInfo('xtc', 'string{}','Template Code'));

			$mail_src = \ebi\Util::file_read(self::mail_template_path($mail_info->name()));
			$varnames = [];

			if(preg_match_all('/\$([\w_]+)/',$mail_src,$m)){
				$varnames = array_unique($m[1]);

				if(preg_match_all('/[ :](var=|counter=|key=)["\']([\w_]+)["\']/',$mail_src,$m)){
					foreach($m[2] as $rtvar){
						$varnames = array_filter($varnames, fn($v) => $v !== $rtvar);
					}
				}
				foreach($varnames as $k => $varname){
					foreach($mail_info->params() as $param){
						if($varname === $param->name()){
							unset($varnames[$k]);
						}
					}
				}
			}
			$mail_info->set_opt('undefined_vars',array_values($varnames));

			return true;
		}
		return false;
	}

	public static function classify_see(string $v): ?array{
		if(strpos($v,'://') !== false){
			return ['type'=>'url','url'=>$v];
		}else if($v[0] === '/'){
			return ['type'=>'endpoint','path'=>$v];
		}else if(strpos($v,'::') !== false){
			[$see_class, $see_method] = explode('::',$v,2);
			return ['type'=>'method','class'=>$see_class,'method'=>$see_method];
		}else if(substr($v,-1) != ':'){
			return ['type'=>'class','class'=>$v];
		}
		return null;
	}

	private static function find_see(string $document): array{
		$see = [];

		if(preg_match_all("/@see\s+(\S+)/",$document,$m)){
			foreach($m[1] as $v){
				$result = self::classify_see(trim($v));
				if(!empty($result)){
					$see[$v] = $result;
				}
			}
		}
		return $see;
	}

	/**
	 * メソッドの情報を取得
	 */
	public static function method_info(string $class, string $method, bool $detail=false, bool $deep=false): DocInfo{
		$ref = new \ReflectionMethod(self::get_class_name($class),$method);
		$is_request_flow = $ref->getDeclaringClass()->isSubclassOf(\ebi\flow\Request::class);
		$method_fullname = $ref->getDeclaringClass()->getName().'::'.$ref->getName();

		if(!is_file($ref->getDeclaringClass()->getFileName())){
			throw new \ebi\exception\NotFoundException();
		}

		$document = '';

		if($is_request_flow){
			try{
				$before_ref = new \ReflectionMethod(self::get_class_name($class),'__before__');

				if(preg_match_all('/@.+$/',self::get_method_document($before_ref),$m)){
					foreach($m[0] as $a){
						$document = $a.PHP_EOL.$document;
					}
				}
			}catch(\ReflectionException){
			}
		}
		$document .= self::get_method_document($ref);
		$src = self::method_src($ref);

		$info = DocInfo::parse($method_fullname,$document);

		self::find_merge_deprecate($info,$document);
		self::find_merge_params($info,$ref->getParameters());
		self::find_merge_request_context($info, $document);

		$info->set_opt('class',self::get_class_name($class));
		$info->set_opt('method',$ref->getName());
		$info->set_opt('see_list',self::find_see($document));

		if(!$info->is_version()){
			$info->version(date('Ymd',filemtime($ref->getDeclaringClass()->getFileName())));
		}

		// サーバー間通信エンドポイント
		if(preg_match('/@s2s/',$document)){
			$info->set_opt('s2s',true);
		}

		// ログイン要件（表示用）
		if(preg_match('/@login_required/',$document)){
			$info->set_opt('login',true);
		}

		// HTTPメソッドの検出
		if(preg_match("/@http_method\s+([^\s]+)/",$document,$m)){
			$info->set_opt('http_method',strtoupper(trim($m[1])));
		}else{
			$info->set_opt('http_method',
				(strpos($src,'$this->is_post()') !== false && strpos($src,'!$this->is_post()') === false) ? 'POST' : null
			);
		}

		$throws = $mail_list = [];
		$use_method_list = $deep
			? array_merge(self::use_method_list($ref->getDeclaringClass()->getName(),$ref->getName()),[$method_fullname])
			: [$method_fullname];
		$use_method_list = array_unique($use_method_list);

		foreach(array_keys($use_method_list) as $k){
			$use_method_list[$k] = preg_replace('/\\\\+/','\\','\\'.ltrim($use_method_list[$k],'\\'));
		}
		krsort($use_method_list);
		$use_method_list = array_unique($use_method_list);

		if($detail){
			$mail_template_list = self::mail_template_list();

			foreach($use_method_list as $class_method){
				[$uclass, $umethod] = explode('::',$class_method);

				try{
					$ref = new \ReflectionMethod($uclass,$umethod);
					$use_method_src = self::method_src($ref);
					$use_method_doc = self::trim_doc($ref->getDocComment());

					$throws = self::find_throws($throws,$use_method_doc,$use_method_src);

					foreach($mail_template_list as $mail_info){
						if(self::find_mail_doc($mail_info, $use_method_src)){
							$mail_list[$mail_info->opt('x_t_code')] = $mail_info;
						}
					}
				}catch(\ReflectionException){
				}
			}
		}
		$info->set_opt('mail_list',$mail_list);
		$info->set_opt('throws',self::merge_find_throws($throws));

		return $info;
	}

	/**
	 * メールテンプレートのパスを取得
	 */
	public static function mail_template_path(string $path): string{
		$dir = \ebi\Conf::get(\ebi\Mail::class.'@resource_path',\ebi\Conf::resource_path('mail'));
		return \ebi\Util::path_absolute($dir, $path);
	}

	/**
	 * メールテンプレート一覧を取得
	 */
	public static function mail_template_list(): array{
		$path = self::mail_template_path('');
		$template_list = [];

		try{
			foreach(\ebi\Util::ls($path,true,'/\.xml$/') as $f){
				$info = new DocInfo();
				$info->name(str_replace(\ebi\Util::path_slash($path,null,true),'',$f->getPathname()));

				try{
					$xml = \ebi\Xml::extract(file_get_contents($f->getPathname()),'mail');

					try{
						$xml->find_get('signature');
					}catch(\ebi\exception\NotFoundException){
						$info->version($xml->in_attr('version',date('Ymd',filemtime($f->getPathname()))));
						$info->set_opt('x_t_code',\ebi\Mail::xtc($info->name()));

						try{
							$subject = trim($xml->find_get('subject')->value() ?? '');
							$info->document($subject);
							$info->set_opt('subject',$subject);
						}catch(\ebi\exception\NotFoundException){
						}
						try{
							$info->document(trim($xml->find_get('summary')->value() ?? ''));
						}catch(\ebi\exception\NotFoundException){
						}
						$template_list[] = $info;
					}
				}catch(\ebi\exception\NotFoundException){
				}
			}
		}catch(\ebi\exception\InvalidArgumentException){
		}
		usort($template_list, fn($a,$b) => strcasecmp($a->name(), $b->name()));

		return $template_list;
	}

	public static function method_src(\ReflectionMethod $ref): string{
		if(is_file($ref->getDeclaringClass()->getFileName())){
			return implode(array_slice(file($ref->getDeclaringClass()->getFileName()),$ref->getStartLine(),($ref->getEndLine()-$ref->getStartLine()-1)));
		}
		return '';
	}

	public static function trim_doc(string $doc): string{
		return trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(['/'.'**','*'.'/'],'',$doc)));
	}

	private static function use_method_list(string $class, string $method, array &$loaded_method_src=[]): array{
		$list = [];

		try{
			$ref = new \ReflectionMethod($class,$method);
			$kname = $ref->getDeclaringClass()->getName().'::'.$ref->getName();

			if(isset($loaded_method_src[$kname])){
				return [];
			}
			$loaded_method_src[$kname] = true;
			$list[$kname] = true;

			if(is_file($ref->getDeclaringClass()->getFileName())){
				$src = self::method_src($ref);
				$vars = ['$this'=>$class];

				foreach($ref->getParameters() as $param){
					if($param->hasType() && $param->getType() instanceof \ReflectionNamedType){
						$type_class = $param->getType()->getName();
						if(class_exists($type_class)){
							$vars['$'.$param->getName()] = $type_class;
						}
					}
				}
				if(preg_match_all('/(\$\w+)\s*=\s*new\s+([\\\\\w]+)/',$src,$m)){
					foreach($m[1] as $k => $v){
						$vars[$v] = $m[2][$k];
					}
				}
				if(preg_match_all('/(\$\w+)\s*=\s*([\\\\\w]+)::(\w+)/',$src,$m)){
					foreach($m[1] as $k => $v){
						try{
							$ref = new \ReflectionMethod($m[2][$k],$m[3][$k]);
							if(preg_match("/@return\s+([^\s]+)(.*)/",self::trim_doc($ref->getDocComment()),$r)){
								$vars[$v] = preg_match('/[A-Z]/',$r[1]) ? $r[1] : $m[2][$k];
							}
						}catch(\ReflectionException){
						}
					}
				}
				if(preg_match_all('/(\$\w+)->(\w+)/',$src,$m)){
					foreach($m[1] as $k => $v){
						if(isset($vars[$v])){
							$list[$vars[$v].'::'.$m[2][$k]] = true;
						}
					}
				}
				if(preg_match_all('/([\\\\\w]+)::(\w+)/',$src,$m)){
					foreach($m[1] as $k => $v){
						if($v == 'self' || $v == 'static'){
							$v = $class;
						}
						$list[$v.'::'.$m[2][$k]] = true;
					}
				}
				foreach(array_keys($list) as $mcm){
					[$c, $m] = explode('::',$mcm);
					$c = str_replace('\\\\','\\',($c[0] !== '\\') ? '\\'.$c : $c);

					if(!isset($loaded_method_src[$c.'::'.$m])){
						foreach(self::use_method_list($c,$m,$loaded_method_src) as $k){
							$list[$k] = true;
						}
					}
				}
			}
		}catch(\ReflectionException){
		}
		return array_keys($list);
	}
}
