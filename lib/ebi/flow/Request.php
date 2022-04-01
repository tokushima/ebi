<?php
namespace ebi\flow;

class Request extends \ebi\Request{
	private $_selected_pattern = [];
	private $_before_redirect;
	private $_after_redirect;
	private $_auth;

	private $_sess;
	private $_login_id;
	private $_login_anon;
	private $_after_vars = [];
	
	public function __construct(){
		parent::__construct();
		$sess_name = md5(\ebi\Flow::workgroup());
		
		$this->_sess = new \ebi\Session($sess_name);
		$this->_login_id = $sess_name.'_LOGIN_';
		$this->_login_anon = \ebi\Annotation::get_class($this,'login',null,__CLASS__);
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
		$this->_sess->vars($key,$val);
	}
	/**
	 * セッションから取得する
	 * @param mixed $d セッションが存在しない場合の代理値
	 * @return mixed
	 */
	public function in_sessions(string $n, $d=null){
		return $this->_sess->in_vars($n,$d);
	}
	/**
	 * セッションから削除する
	 */
	public function rm_sessions(...$args): void{
		call_user_func_array([$this->_sess,'rm_vars'], $args);
	}
	/**
	 * 指定のキーが存在するか
	 */
	public function is_sessions(string $n): bool{
		return $this->_sess->is_vars($n);
	}

	/**
	 * 前処理、入力値のバリデーションやログイン処理を行う
	 */
	public function before(): void{
		$annon = $this->request_validation(['user_role']);

		if(isset($this->_selected_pattern['auth'])){
			$auth_ref = new \ReflectionClass($this->_selected_pattern['auth']);
			$this->_auth = $auth_ref->newInstance();

			if(!($this->_auth instanceof \ebi\flow\AuthenticationHandler)){
				throw new \ebi\exception\NotImplementedException();
			}
		}

		if(!$this->is_user_logged_in()){
			if(isset($this->_auth) && $this->_auth->remember_me($this) === true){
				$this->after_user_login();
			}
			if(!$this->is_user_logged_in() && (isset($this->_login_anon) || isset($this->_auth))){
				if(
					isset($this->_selected_pattern['action']) && 
					strpos($this->_selected_pattern['action'],'::do_login') === false
				){
					if(!$this->is_user_logged_in()){
						if(!($this->_selected_pattern['unauthorized_redirect'] ?? true)){
							\ebi\HttpHeader::send_status(401);
							throw new \ebi\exception\UnauthorizedException('Unauthorized');
						}
					}
					if(
						strpos($this->_selected_pattern['action'],'::do_login') === false &&
						strpos($this->_selected_pattern['action'],'::do_logout') === false
					){
						$this->set_logged_in_redirect_to(\ebi\Request::current_url().\ebi\Request::request_string(true));
					}
					$this->_sess->vars(__CLASS__.'_login_vars',[time(), $this->ar_vars()]);
					
					if(array_key_exists('@',$this->_selected_pattern)){
						$this->set_before_redirect('do_login');
					}else{
						$this->set_before_redirect('login');
					}
				}
			}
		}

		if($this->is_user_logged_in()){
			if(isset($this->_login_anon['type']) && !($this->user() instanceof $this->_login_anon['type'])){
				\ebi\HttpHeader::send_status(401);
				throw new \ebi\exception\UnauthorizedException();
			}
			if(isset($annon['user_role']) || isset($this->_login_anon['user_role'])){
				if(
					!in_array(\ebi\UserRole::class,\ebi\Util::get_class_traits(get_class($this->user()))) ||
					(isset($this->_login_anon['user_role']) && !in_array($this->_login_anon['user_role'],$this->user()->get_role())) ||
					(isset($annon['user_role']['value']) && !in_array($annon['user_role']['value'],$this->user()->get_role()))
				){
					\ebi\HttpHeader::send_status(403);
					throw new \ebi\exception\AccessDeniedException();
				}
			}
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
	 */
	public function after(): void{
		if($this->is_vars('callback')){
			$this->_after_vars['callback'] = $this->in_vars('callback');
		}
	}
	
	/**
	 * Flowの結果に返却値を追加する
	 * @return array 
	 * @compatibility
	 */
	public function get_after_vars(){
		return $this->_after_vars;
	}
	
	/**
	 * ログインしているユーザのモデル
	 * @return mixed
	 */
	public function user(){
		if(func_num_args() > 0){
			$user = func_get_arg(0);
			
			if(isset($this->_login_anon['type']) && !($user instanceof $this->_login_anon['type'])){
				throw new \ebi\exception\IllegalDataTypeException();
			}
			$this->sessions($this->_login_id.'USER', $user);
		}
		return $this->in_sessions($this->_login_id.'USER');
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
		return $this->_login_id;
	}
	/**
	 * ログイン済みか
	 */
	public function is_user_logged_in(): bool{
		return ($this->in_sessions($this->_login_id) !== null);
	}
	
	/**
	 * ログイン完了処理
	 */
	private function after_user_login(): void{
		$this->sessions($this->_login_id,$this->_login_id);
		session_regenerate_id(true);
	}
	/**
	 * ログイン処理
	 */
	public function do_login(): array{
		if($this->_sess->is_vars(__CLASS__.'_login_vars')){
			$data = $this->_sess->in_vars(__CLASS__.'_login_vars');
			if(($data[0] + 5) > time()){
				foreach($data[1] as $k => $v){
					if(!$this->is_vars($k)){
						$this->vars($k,$v);
					}
				}
			}
			$this->_sess->rm_vars(__CLASS__.'_login_vars');
		}

		if(
			isset($this->_auth) &&
			!$this->is_user_logged_in() &&
			$this->_auth->login_condition($this) === true
		){ 
			$this->after_user_login();
		}
		$rtn_vars = [];
		if($this->is_user_logged_in()){
			if(isset($this->_auth)){
				$this->_auth->after_login($this);
				$rtn_vars = $this->_auth->get_after_vars_login($this);
			}
			$logged_in_redirect_to = $this->in_sessions('logged_in_redirect_to');
			$this->rm_sessions('logged_in_redirect_to');
			
			if(isset($this->_selected_pattern['logged_in_after'])){
				$logged_in_redirect_to = $this->_selected_pattern['logged_in_after'];
			}
			if(empty($this->get_after_redirect()) && !empty($logged_in_redirect_to)){
				$this->set_after_redirect($logged_in_redirect_to);
			}
		}else{
			if(array_key_exists('after',$this->_selected_pattern)){
				$this->set_after_redirect($this->_selected_pattern['after']);
			}
			if(empty($this->get_after_redirect())){
				\ebi\HttpHeader::send_status(401);

				$html = isset($this->_selected_pattern['action']) ? 
					'/resources/templates/'.preg_replace('/^.+::/','',$this->_selected_pattern['action']).'.html' : '';
				if(!(
					isset($this->_selected_pattern['template']) || 
					(
						isset($this->_selected_pattern['@']) && 
						(
							!empty($html) && (
								is_file($this->_selected_pattern['@'].$html) ||
								(isset($this->_selected_pattern['&']) && is_file(dirname($this->_selected_pattern['@'],$this->_selected_pattern['&']).$html))
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
		if($this->_auth instanceof \ebi\flow\AuthenticationHandler){
			$vars = $this->_auth->before_logout($this);
		}
		
		$this->rm_sessions($this->_login_id.'USER');
		$this->rm_sessions($this->_login_id);
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