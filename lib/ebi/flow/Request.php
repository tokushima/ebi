<?php
namespace ebi\flow;
/**
 * リクエストやセッションを処理する
 * @author tokushima
 *
 */
class Request extends \ebi\Request{
	use \ebi\Plugin, \ebi\FlowPlugin;
	
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
	 * セッションにセットする
	 * @param string $key
	 * @param mixed $val
	 */
	public function sessions($key,$val){
		$this->sess->vars($key,$val);
	}
	/**
	 * セッションから取得する
	 * @param string $n 取得する定義名
	 * @param mixed $d セッションが存在しない場合の代理値
	 * @return mixed
	 */
	public function in_sessions($n,$d=null){
		return $this->sess->in_vars($n,$d);
	}
	/**
	 * セッションから削除する
	 * @param string $n 削除する定義名
	 */
	public function rm_sessions($n){
		call_user_func_array([$this->sess,'rm_vars'],func_get_args());
	}
	/**
	 * 指定のキーが存在するか
	 * @param string $n
	 * @return boolean
	 */
	public function is_sessions($n){
		return $this->sess->is_vars($n);
	}
	/**
	 * 前処理、入力値のバリデーションやログイン処理を行う
	 * __before__メソッドを定義することで拡張する
	 */
	public function before(){
		list(,$method) = explode('::',$this->get_selected_pattern()['action']);
		$annon = \ebi\Annotation::get_method(get_class($this), $method,['http_method','request','user_role']);
		
		if(isset($annon['http_method']['value']) && strtoupper($annon['http_method']['value']) != \ebi\Request::method()){
			throw new \ebi\exception\BadMethodCallException('Method Not Allowed');
		}
		if(isset($annon['request'])){
			foreach($annon['request'] as $k => $an){
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
		
		if(!$this->is_user_logged_in()){
			if($this->has_object_plugin('remember_me')){
				/**
				 * remember meの条件処理
				 * @param \ebi\flow\Request $arg1
				 * @return boolean ログイン成功時にはtrueを返す
				 */
				if($this->call_object_plugin_funcs('remember_me',$this) === true){
					$this->after_user_login();
				}
			}
			
			// ログインが必要な条件
			if(!$this->is_user_logged_in() && (isset($this->login_anon) || $this->has_object_plugin('login_condition'))){
				$selected_pattern = $this->get_selected_pattern();
				
				if(array_key_exists('action',$selected_pattern) && strpos($selected_pattern['action'],'::do_login') === false){
					if($this->has_object_plugin('before_login_redirect')){
						/**
						 * ログイン機能へのリダイレクト前処理
						 * @param \ebi\flow\Request $arg1
						 */
						$this->call_object_plugin_funcs('before_login_redirect',$this);
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
		
		// ロールの確認
		if($this->is_user_logged_in() && (isset($annon['user_role']) || isset($this->login_anon['user_role']))){
			if(
				!in_array(\ebi\UserRole::class,\ebi\Util::get_class_traits(get_class($this->user()))) || 
				(isset($this->login_anon['user_role']) && !in_array($this->login_anon['user_role'],$this->user()->get_role())) ||
				(isset($annon['user_role']['value']) && !in_array($annon['user_role']['value'],$this->user()->get_role()))
			){
				throw new \ebi\exception\NotPermittedException();
			}
		}
		if(method_exists($this,'__before__')){
			$this->__before__();
		}
		if($this->has_object_plugin('before_flow_action_request')){
			/**
			 * アクション前処理
			 * @param \ebi\flow\Request $arg1
			 */
			$this->call_object_plugin_funcs('before_flow_action_request',$this);
		}
	}
	
	/**
	 * ログイン後、ログイン済みの場合にリダイレクトするURLを設定する
	 * @param string $url
	 */
	public function set_logged_in_redirect_to($url){
		$this->sessions('logged_in_redirect_to',$url);
	}
	
	/**
	 * 後処理
	 * __after__メソッドを定義することで拡張する
	 */
	public function after(){
		if(method_exists($this,'__after__')){
			$this->__after__();
		}
		if($this->has_object_plugin('after_flow_action_request')){
			/**
			 * アクション後処理
			 * @param \ebi\flow\Request $arg1
			 */
			$this->call_object_plugin_funcs('after_flow_action_request',$this);
		}
		if($this->has_object_plugin('get_after_vars_request')){
			/**
			 * アクションに連想配列を追加する
			 * @return mixed{} 
			 */
			foreach($this->get_object_plugin_funcs('get_after_vars_request') as $o){
				$rtn = self::call_func($o);
				
				if(is_array($rtn)){
					$this->after_vars = array_merge($this->after_vars,$rtn);
				}
			}
		}
	}
	
	/**
	 * Flowの結果に返却値を追加する
	 * @return mixed{}
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
			
			if(isset($this->login_anon) && isset($this->login_anon['type'])){
				$class = str_replace('.',"\\",$this->login_anon['type']);
				
				if($class[0] != "\\"){
					$class= "\\".$class;
				}
				if(!($user instanceof $class)){
					throw new \ebi\exception\UnauthorizedTypeException();
				}
			}
			$this->sessions($this->login_id.'USER',$user);
		}
		return $this->in_sessions($this->login_id.'USER');
	}
	/**
	 * ログインセッション識別子
	 * @return string
	 */
	public function user_login_session_id(){
		return $this->login_id;
	}
	/**
	 * ログイン済みか
	 * @return boolean
	 */
	public function is_user_logged_in(){
		return ($this->in_sessions($this->login_id) !== null);
	}
	
	/**
	 * ログイン完了処理
	 */
	private function after_user_login(){
		$this->sessions($this->login_id,$this->login_id);
		session_regenerate_id(true);
	}
	/**
	 * ログイン処理
	 * 
	 * @automap
	 * @version 1.2.11
	 */
	public function do_login(){
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
			 * @return boolean ログイン成功時にはtrueを返す
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
				
				if(
					!isset($pattern['template']) && 
					!(isset($pattern['@']) && 
					is_file(($pattern['@'].'/resources/templates/'.preg_replace('/^.+::/','',$pattern['action']).'.html')))
				){
					throw new \ebi\exception\UnauthorizedException();
				}
			}
		}
		return $rtn_vars;
	}
	/**
	 * ログアウト
	 * @automap
	 * @version 1.2.11
	 */
	public function do_logout(){
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
	 * @version 1.2.11
	 */
	public function noop(){
		return $this->ar_vars();
	}
}