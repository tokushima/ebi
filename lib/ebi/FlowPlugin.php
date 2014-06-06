<?php
namespace ebi;

trait FlowPlugin{
	private $_selected_pattern = [];
	private $_template_block = null;
	private $_template = null;
	private $_before_redirect;
	private $_after_redirect;
	
	public function set_pattern(array $selected_pattern){
		$this->_selected_pattern = $selected_pattern;
	}
	public function get_flow_plugins(){
		return [];
	}
	
	
	public function set_after_redirect($url){
		$this->_after_redirect = $url;
	}
	public function get_after_redirect(){
		return $this->_after_redirect;
	}
	public function set_before_redirect($url){
		$this->_before_redirect = $url;
	}
	public function get_before_redirect(){
		return $this->_before_redirect;
	}	
	
	public function get_selected_pattern(){
		return $this->_selected_pattern;
	}
	public function get_after_vars(){
		return [];
	}
	public function get_template_block(){
		return $this->_template_block;
	}
	public function set_template_block($block){
		$this->_template_block = $block;
	}
	public function get_template(){
		return $this->_template;
	}
	public function set_template($template){
		$this->_template = $template;
	}
	public function map_arg($name,$default=null){
		return (isset($this->_selected_pattern['args'][$name])) ? $this->_selected_pattern['args'][$name] : $default;
	}
	public function before(){
	}
	public function after(){
	}
}