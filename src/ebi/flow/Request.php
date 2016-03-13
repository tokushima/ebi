<?php
namespace ebi\flow;
/**
 * リクエストやセッションを処理するactionベース
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
		$sess_name = md5(\ebi\Flow::entry_file());
		
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
	public function before(){
		list(,$method) = explode('::',$this->get_selected_pattern()['action']);
		$annon = \ebi\Annotation::get_method(get_class($this), $method,['http_method','request']);
			
		if(isset($annon['http_method']['value']) && strtoupper($annon['http_method']['value']) != \ebi\Request::method()){
			throw new \ebi\exception\BadMethodCallException('Method Not Allowed');
		}
		if(isset($annon['request'])){
			foreach($annon['request'] as $k => $an){
				if(isset($an['type'])){
					try{
						\ebi\Validator::type($k,$this->in_vars($k),$an);
					}catch(\InvalidArgumentException $e){
						\ebi\Exceptions::add($e,$k);
					}
					\ebi\Validator::value($k, $this->in_vars($k), $an);
				}
			}
		}
		\ebi\Exceptions::throw_over();
		
		if(method_exists($this,'__before__')){
			$this->__before__();
		}
		if($this->has_object_plugin('before_flow_action_request')){
			/**
			 * 前処理
			 * @param \ebi\flow\Request $arg1
			 */
			$this->call_object_plugin_funcs('before_flow_action_request',$this);
		}
		if(
			!$this->is_login() && 
			((isset($this->login_anon)) || $this->has_object_plugin('login_condition'))
		){
			$this->login_required();
		}
	}
	public function after(){
		if(method_exists($this,'__after__')){
			$this->__after__();
		}
		if($this->has_object_plugin('after_flow_action_request')){
			/**
			 * 後処理
			 * @param \ebi\flow\Request $arg1
			 */
			$this->call_object_plugin_funcs('after_flow_action_request',$this);
		}
		if($this->has_object_plugin('get_after_vars_request')){
			/**
			 * Flowの結果に返却値を追加する
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
	 * ログインしていない場合にログイン処理を実行する
	 * @throws \LogicException
	 */
	private function login_required(){
		$selected_pattern = $this->get_selected_pattern();
		
		if(!$this->is_login() 
			&& array_key_exists('action',$selected_pattern)
			&& strpos($selected_pattern['action'],'::do_login') === false
		){
			if($this->has_object_plugin('before_login_required')){
				/**
				 * ログイン処理の前処理
				 * @param \ebi\flow\Request $arg1
				 */
				$this->call_object_plugin_funcs('before_login_required',$this);
			}
			if(strpos($selected_pattern['action'],'::do_logout') === false){
				$this->set_login_redirect(\ebi\Request::current_url().\ebi\Request::request_string(true));
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

	/**
	 * ログインしているユーザのモデル
	 * @throws \LogicException
	 * @return mixed
	 */
	public function user(){
		if(func_num_args() > 0){
			$user = func_get_arg(0);
			if(isset($this->login_anon) && isset($this->login_anon['type'])){
				$class = str_replace('.',"\\",$this->login_anon['type']);
				if($class[0] != "\\") $class= "\\".$class;
				if(!($user instanceof $class)){
					throw new \ebi\exception\UnauthorizedTypeException();
				}
			}
			$this->sessions($this->login_id.'USER',$user);
		}
		return $this->in_sessions($this->login_id.'USER');
	}
	/**
	 * ログイン済みか
	 * @return boolean
	 */
	public function is_login(){
		return ($this->in_sessions($this->login_id) !== null);
	}
	/**
	 * ログイン後のリダイレクト先設定
	 * @param string $url
	 */
	public function set_login_redirect($url){
		$this->sessions('logined_redirect_to',$url);
	}
	/**
	 * ログイン
	 * @arg string $login_redirect ログイン後にリダイレクトされるマップ名
	 * @automap
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
		if($this->is_login()){
			if($this->map_arg('login_redirect') != null){
				$this->sessions('logined_redirect_to',$this->map_arg('login_redirect'));
			}
		}else{
			if(!$this->is_sessions('logined_redirect_to') && $this->map_arg('login_redirect') != null){
				$this->sessions('logined_redirect_to',$this->map_arg('login_redirect'));
			}
			if(!$this->has_object_plugin('login_condition') || $this->call_object_plugin_func('login_condition',$this) === false){
				$this->call_object_plugin_func('login_invalid',$this);
			}else{
				$this->sessions($this->login_id,$this->login_id);
				session_regenerate_id(true);
				/**
				 * ログイン後処理
				 * @param \ebi\flow\Request $arg1
				 */
				$this->call_object_plugin_funcs('after_login',$this);
			}
		}
		
		$rtn_vars = ['login'=>$this->is_login()];
		
		if($this->is_login()){
			$redirect_to = $this->in_sessions('logined_redirect_to');
			$this->rm_sessions('logined_redirect_to');
			/**
			 * ログイン処理の後処理
			 * @param \ebi\flow\Request $arg1
			 */
			$vars = $this->call_object_plugin_funcs('after_do_login',$this);

			if(!empty($redirect_to)){
				$this->set_after_redirect($redirect_to);
			}
			if(!empty($vars) && is_array($vars)){
				$rtn_vars = array_merge($rtn_vars,$vars);
			}
		}else{
			\ebi\HttpHeader::send_status(401);
			$pattern = $this->get_selected_pattern();
			
			if(!isset($pattern['template']) && !(isset($pattern['@']) && is_file($t=($pattern['@'].'/resources/templates/'.preg_replace('/^.+::/','',$pattern['action']).'.html')))){
				throw new \ebi\exception\UnauthorizedException();
			}
		}
		return $rtn_vars;
	}
	/**
	 * ログアウト
	 * @automap
	 */
	public function do_logout(){
		$this->rm_sessions('logined_redirect_to');
		$this->rm_sessions($this->login_id.'USER');
		$this->rm_sessions($this->login_id);
		session_regenerate_id(true);
		
		if($this->map_arg('logout_redirect') != null){
			$this->set_after_redirect($this->map_arg('logout_redirect'));
		}
	}
	public function noop(){
		return $this->ar_vars();
	}
}