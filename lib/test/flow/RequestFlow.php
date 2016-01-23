<?php
namespace test\flow;


class RequestFlow extends \ebi\flow\Request{
	/**
	 * aaa
	 * @request string $abc @['require'=>true]
	 * @request string $def @['require'=>true]
	 * @request integer $ghi
	 */
	public function require_vars(){
		$this->is_post();
	}
	
	/**
	 * @http_method POST
	 */
	public function require_post(){
		$this->in_vars('abc');
	}
	/**
	 * @http_method GET
	 */
	public function require_get(){
	
	}
}