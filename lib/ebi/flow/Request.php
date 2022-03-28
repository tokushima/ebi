<?php
namespace ebi\flow;

class Request extends \ebi\Request{
	use \ebi\Plugin;

	private $_selected_pattern = [];
	private $_template = null;
	private $_before_redirect;
	private $_after_redirect;

	private $sess;
	private $login_id;
	private $login_anon;
	private $after_vars = [];
	
	public function __construct(){
		parent::__construct();
		$sess_name = md5(\ebi\Flow::workgroup());
		
		$this->sess = new \ebi\Session($sess_name);
		$this->login_id = $sess_name.'_LOGIN_';
		$this->login_anon = \ebi\Annotation::get_class($this,'login',null,__CLASS__);
	}	

	/**
	 * Flowが利用
	 */
	final public function set_pattern(array $selected_pattern): void{
		$this->_selected_pattern = $selected_pattern;
	}
	
	/**
	 * action実行後にリダイレクトするURL
	 */
	public function set_after_redirect(string $url): void{
		$this->_after_redirect = $url;
	}
	/**
	 * Flowが利用
	 */
	final public function get_after_redirect(): ?string{
		return $this->_after_redirect;
	}
	/**
	 * action実行前にリダイレクトするURL
	 */
	public function set_before_redirect(string $url): void{
		$this->_before_redirect = $url;
	}
	/**
	 * Flowが利用
	 */
	final public function get_before_redirect(): ?string{
		return $this->_before_redirect;
	}	
	
	/**
	 * マッチしたパターンを取得
	 */
	public function get_selected_pattern(): array{
		return $this->_selected_pattern;
	}

	/**
	 * Flowが利用
	 */
	final public function get_template(): ?string{
		return $this->_template;
	}
	/**
	 * テンプレートを上書きする
	 */
	public function set_template(string $template): void{
		$this->_template = $template;
	}
	/**
	 * mapに渡されたargsを取得する
	 * @param mixed $default
	 * @return mixed
	 */
	public function map_arg(string $name, ?string $default=null){
		return (isset($this->_selected_pattern['args'][$name])) ? $this->_selected_pattern['args'][$name] : $default;
	}

	/**
	 * リクエストのバリデーション
	 */
	protected function request_validation(array $doc_names=[]): array{
		$doc_names = empty($doc_names) ? ['http_method','request'] : array_merge(['http_method','request'],$doc_names);
		[,$method] = explode('::',$this->get_selected_pattern()['action']);
		$ann = \ebi\Annotation::get_method(get_class($this), $method,$doc_names);
		
		if(isset($ann['http_method']['value']) && strtoupper($ann['http_method']['value']) != \ebi\Request::method()){
			throw new \ebi\exception\BadMethodCallException('Method Not Allowed');
		}
		if(isset($ann['request'])){
			foreach($ann['request'] as $k => $an){
				if(isset($an['type'])){
					if($an['type'] == 'file'){
						if(isset($an['require']) && $an['require'] === true){
							if(!$this->has_file($k)){
								\ebi\Exceptions::add(new \ebi\exception\RequiredException($k.' required'),$k);
							}else{
								if(isset($an['max'])){
									$filesize = is_file($this->file_path($k)) ? filesize($this->file_path($k)) : 0;
									
									if($filesize <= 0 || ($filesize/1024/1024) > $an['max']){
										\ebi\Exceptions::add(new \ebi\exception\MaxSizeExceededException($k.' exceeds maximum'),$k);
									}
								}
							}
						}
					}else{
						try{
							\ebi\Validator::type($k,$this->in_vars($k),$an);
						}catch(\ebi\exception\InvalidArgumentException $e){
							\ebi\Exceptions::add($e,$k);
						}
						\ebi\Validator::value($k, $this->in_vars($k), $an);
					}
				}
			}
		}
		\ebi\Exceptions::throw_over();
		
		return $ann;
	}



	/**
	 * セッションにセットする
	 * @param mixed $val
	 */
	public function sessions(string $key, $val): void{
		$this->sess->vars($key,$val);
	}
	/**
	 * セッションから取得する
	 * @param mixed $d セッションが存在しない場合の代理値
	 * @return mixed
	 */
	public function in_sessions(string $n, $d=null){
		return $this->sess->in_vars($n,$d);
	}
	/**
	 * セッションから削除する
	 */
	public function rm_sessions(...$args): void{
		call_user_func_array([$this->sess,'rm_vars'], $args);
	}
	/**
	 * 指定のキーが存在するか
	 */
	public function is_sessions(string $n): bool{
		return $this->sess->is_vars($n);
	}
	/**
	 * 前処理、入力値のバリデーションやログイン処理を行う
	 * __before__メソッドを定義することで拡張する
	 */
	public function before(): void{
		$annon = $this->request_validation(['user_role']);
		
		if(!$this->is_user_logged_in()){
			if($this->has_object_plugin('remember_me')){
				/**
				 * remember meの条件処理
				 * @param \ebi\flow\Request $arg1
				 * @return bool ログイン成功時にはtrueを返す
				 */
				if($this->call_object_plugin_funcs('remember_me',$this) === true){
					$this->after_user_login();
				}
			}
			
			if(!$this->is_user_logged_in() && (isset($this->login_anon) || $this->has_object_plugin('login_condition'))){
				$selected_pattern = $this->get_selected_pattern();
				
				if(array_key_exists('action',$selected_pattern) && strpos($selected_pattern['action'],'::do_login') === false){
					if(!$this->is_user_logged_in()){
						if(!($selected_pattern['unauthorized_redirect'] ?? true)){
							\ebi\HttpHeader::send_status(401);
							throw new \ebi\exception\UnauthorizedException('Unauthorized');
						}
					}

					if(
						strpos($selected_pattern['action'],'::do_login') === false &&
						strpos($selected_pattern['action'],'::do_logout') === false
					){
						$this->set_logged_in_redirect_to(\ebi\Request::current_url().\ebi\Request::request_string(true));
					}
					$req = new \ebi\Request();
					$this->sess->vars(__CLASS__.'_login_vars',[time(),$req->ar_vars()]);
					
					if(array_key_exists('@',$selected_pattern)){
						$this->set_before_redirect('do_login');
					}else{
						$this->set_before_redirect('login');
					}
				}
			}
		}
		
		if($this->is_user_logged_in()){
			if(isset($this->login_anon['type']) && !($this->user() instanceof $this->login_anon['type'])){
				\ebi\HttpHeader::send_status(401);
				throw new \ebi\exception\UnauthorizedException();
			}
			if(isset($annon['user_role']) || isset($this->login_anon['user_role'])){
				if(
					!in_array(\ebi\UserRole::class,\ebi\Util::get_class_traits(get_class($this->user()))) ||
					(isset($this->login_anon['user_role']) && !in_array($this->login_anon['user_role'],$this->user()->get_role())) ||
					(isset($annon['user_role']['value']) && !in_array($annon['user_role']['value'],$this->user()->get_role()))
				){
					\ebi\HttpHeader::send_status(403);
					throw new \ebi\exception\AccessDeniedException();
				}
			}
		}
		if(method_exists($this,'__before__')){
			call_user_func([$this, '__before__']);
		}

		$this->cors();
	}
	
	/**
	 * ログイン後、ログイン済みの場合にリダイレクトするURLを設定する
	 */
	public function set_logged_in_redirect_to(string $url): void{
		$this->sessions('logged_in_redirect_to',$url);
	}
	
	/**
	 * 後処理
	 * __after__メソッドを定義することで拡張する
	 */
	public function after(): void{
		if(method_exists($this,'__after__')){
			call_user_func([$this, '__after__']);
		}

		if($this->is_vars('callback')){
			$this->after_vars['callback'] = $this->in_vars('callback');
		}
	}
	
	/**
	 * Flowの結果に返却値を追加する
	 * @return array 
	 * @compatibility
	 */
	public function get_after_vars(){
		return $this->after_vars;
	}
	
	/**
	 * ログインしているユーザのモデル
	 * @return mixed
	 */
	public function user(){
		if(func_num_args() > 0){
			$user = func_get_arg(0);
			
			if(isset($this->login_anon['type']) && !($user instanceof $this->login_anon['type'])){
				throw new \ebi\exception\IllegalDataTypeException();
			}
			$this->sessions($this->login_id.'USER', $user);
		}
		return $this->in_sessions($this->login_id.'USER');
	}
	
	/**
	 * ログイン状態にする
	 * @param mixed $user
	 */
	protected function force_user_login($user): void{
		$this->user($user);
		$this->after_user_login();
	}
	
	/**
	 * ログインセッション識別子
	 */
	public function user_login_session_id(): string{
		return $this->login_id;
	}
	/**
	 * ログイン済みか
	 */
	public function is_user_logged_in(): bool{
		return ($this->in_sessions($this->login_id) !== null);
	}
	
	/**
	 * ログイン完了処理
	 */
	private function after_user_login(): void{
		$this->sessions($this->login_id,$this->login_id);
		session_regenerate_id(true);
	}
	/**
	 * ログイン処理
	 */
	public function do_login(): array{
		if($this->sess->is_vars(__CLASS__.'_login_vars')){
			$data = $this->sess->in_vars(__CLASS__.'_login_vars');
			if(($data[0] + 5) > time()){
				foreach($data[1] as $k => $v){
					if(!$this->is_vars($k)){
						$this->vars($k,$v);
					}
				}
			}
			$this->sess->rm_vars(__CLASS__.'_login_vars');
		}
		$pattern = $this->get_selected_pattern();

		if(!$this->is_user_logged_in()){
			/**
			 * ログイン条件処理
			 * @param \ebi\flow\Request $arg1
			 * @return bool ログイン成功時にはtrueを返す
			 */
			if($this->call_object_plugin_func('login_condition',$this) === true){
				$this->after_user_login();
			}
		}
		$rtn_vars = ['login'=>$this->is_user_logged_in()];
		
		if($this->is_user_logged_in()){
			/**
			 * ログイン後またはログイン済みの場合の後処理
			 * @param \ebi\flow\Request $arg1
			 */
			$this->call_object_plugin_funcs('after_login',$this);
			
			$logged_in_redirect_to = $this->in_sessions('logged_in_redirect_to');
			$this->rm_sessions('logged_in_redirect_to');
			
			if(isset($pattern['logged_in_after'])){
				$logged_in_redirect_to = $pattern['logged_in_after'];
			}
			if(empty($this->get_after_redirect()) && !empty($logged_in_redirect_to)){
				$this->set_after_redirect($logged_in_redirect_to);
			}
			
			/**
			 * ログイン処理後にアクションに連想配列を追加する
			 * @param \ebi\flow\Request $arg1
			 */
			$vars = $this->call_object_plugin_funcs('get_after_vars_login',$this);
			
			if(!empty($vars) && is_array($vars)){
				$rtn_vars = array_merge($rtn_vars,$vars);
			}
		}else{
			if(array_key_exists('after',$pattern)){
				$this->set_after_redirect($pattern['after']);
			}
			if(empty($this->get_after_redirect())){
				\ebi\HttpHeader::send_status(401);

				$html = isset($pattern['action']) ? 
					'/resources/templates/'.preg_replace('/^.+::/','',$pattern['action']).'.html' : '';
				
				if(!(
					isset($pattern['template']) || 
					(
						isset($pattern['@']) && 
						(
							!empty($html) && (
								is_file($pattern['@'].$html) ||
								(isset($pattern['&']) && is_file(dirname($pattern['@'],$pattern['&']).$html))
							)
						)
					)
				)){
					throw new \ebi\exception\UnauthorizedException();
				}
			}
		}
		return $rtn_vars;
	}
	/**
	 * ログアウト
	 */
	public function do_logout(): void{
		/**
		 * ログアウトの前処理
		 * @param \ebi\flow\Request $arg1
		 */
		$this->call_object_plugin_funcs('before_logout',$this);
		
		$this->rm_sessions($this->login_id.'USER');
		$this->rm_sessions($this->login_id);
		session_regenerate_id(true);
	}
	/**
	 * 何も処理をせずに、varsを返す
	 */
	public function noop(): array{
		return $this->ar_vars();
	}

	private function cors(): void{
		$request_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
		
		if(!empty($request_origin)){
			/**
			 * @param string[] $cors_origin 許可するURL
			 */
			$origin = \ebi\Conf::get('cors_origin');
			
			/**
			 * @param bool $cors_debug ORIGINを常に許可する
			 */
			if(empty($origin) && \ebi\Conf::get('cors_debug',false) === true){
				$origin = [$request_origin];
			}
			if(!is_array($origin)){
				$origin = [$origin];
			}
			if(in_array($request_origin, $origin)){
				\ebi\HttpHeader::send('Access-Control-Allow-Origin', $request_origin);
				\ebi\HttpHeader::send('Access-Control-Allow-Credentials','true');
				
				if(\ebi\Request::method() == 'OPTIONS' && $request_origin != '*'){
					$request_method = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] ?? '';
					$request_header = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? '';
			
					/**
					 * @param int $cors_max_age プリフライトの応答をキャッシュする秒数
					 */
					$max_age = (int)\ebi\Conf::get('cors_max_age',0);

					if(!empty($request_method)){
						\ebi\HttpHeader::send('Access-Control-Allow-Methods', $request_method);
					}
					if(!empty($request_header)){
						\ebi\HttpHeader::send('Access-Control-Allow-Headers', $request_header);
					}
					if($max_age > 0){
						\ebi\HttpHeader::send('Access-Control-Max-Age', $max_age);
					}
					exit;
				}
			}
		}
	}
}