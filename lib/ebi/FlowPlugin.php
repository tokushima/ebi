<?php
namespace ebi;
/**
 * Flowの機能拡張
 * @author tokushima
 */
trait FlowPlugin{
	private $_selected_pattern = [];
	private $_template = null;
	private $_before_redirect;
	private $_after_redirect;
	
	/**
	 * Flowが利用
	 * @param array $selected_pattern
	 */
	final public function set_pattern(array $selected_pattern){
		$this->_selected_pattern = $selected_pattern;
	}
	/**
	 * Flowにpluginをさす
	 * @return string[]
	 */
	public function get_flow_plugins(){
		return [];
	}
	
	/**
	 * action実行後にリダイレクトするURL
	 * @param string $url
	 */
	public function set_after_redirect($url){
		$this->_after_redirect = $url;
	}
	/**
	 * Flowが利用
	 */
	final public function get_after_redirect(){
		return $this->_after_redirect;
	}
	/**
	 * action実行前にリダイレクトするURL
	 * @param string $url
	 */
	public function set_before_redirect($url){
		$this->_before_redirect = $url;
	}
	/**
	 * Flowが利用
	 */
	final public function get_before_redirect(){
		return $this->_before_redirect;
	}	
	
	/**
	 * マッチしたパターンを取得
	 * @return mixed{}
	 */
	public function get_selected_pattern(){
		return $this->_selected_pattern;
	}
	/**
	 * 結果に値を追加する
	 * @return mixed{}
	 */
	public function get_after_vars(){
		return [];
	}

	/**
	 * Flowが利用
	 */
	final public function get_template(){
		return $this->_template;
	}
	/**
	 * テンプレートを上書きする
	 * @param string $template
	 */
	public function set_template($template){
		$this->_template = $template;
	}
	/**
	 * mapに渡されたargsを取得する
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public function map_arg($name,$default=null){
		return (isset($this->_selected_pattern['args'][$name])) ? $this->_selected_pattern['args'][$name] : $default;
	}
	/**
	 * action実行前に実行される
	 */
	public function before(){
		$this->request_validation();
	}
	/**
	 * action実行後に実行される
	 */
	public function after(){
	}
	
	/**
	 * リクエストのバリデーション
	 * @param string[] $doc_names
	 * @throws \ebi\exception\BadMethodCallException
	 * @return array
	 */
	protected function request_validation(array $doc_names=[]){
		$doc_names = empty($doc_names) ? ['http_method','request'] : array_merge(['http_method','request'],$doc_names);
		[,$method] = explode('::',$this->get_selected_pattern()['action']);
		$annon = \ebi\Annotation::get_method(get_class($this), $method,$doc_names);
		
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
		
		return $annon;
	}
}