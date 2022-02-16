<?php
namespace ebi;

trait FlowPlugin{
	private $_selected_pattern = [];
	private $_template = null;
	private $_before_redirect;
	private $_after_redirect;
	
	/**
	 * Flowが利用
	 */
	final public function set_pattern(array $selected_pattern): void{
		$this->_selected_pattern = $selected_pattern;
	}
	/**
	 * Flowにpluginをさす
	 */
	public function get_flow_plugins(): array{
		return [];
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
	 * 結果に値を追加する
	 * @return array
	 * @compatibility
	 */
	public function get_after_vars(){
		return [];
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
	 * action実行前に実行される
	 */
	public function before(): void{
		$this->request_validation();
	}
	/**
	 * action実行後に実行される
	 */
	public function after(): void{
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
}