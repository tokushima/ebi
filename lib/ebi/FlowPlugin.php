<?php
namespace ebi;
/**
 * Flowの機能拡張
 * @author tokushima
 */
trait FlowPlugin{
	private $_selected_pattern = [];
	private $_template_block = null;
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
	final public function get_template_block(){
		return $this->_template_block;
	}
	/**
	 * テンプレートブロックを上書きする
	 * @param string $block
	 */
	public function set_template_block($block){
		$this->_template_block = $block;
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
	}
	/**
	 * action実行後に実行される
	 */
	public function after(){
	}
}