<?php
namespace test\flow;


class RequestFlow extends \ebi\flow\Request{
	/**
	 * @request string $abc @['require'=>true]
	 * @request string $def @['require'=>true]
	 * @request integer $ghi
	 */
	public function require_vars(){
	}
	
	/**
	 * @http_method POST
	 */
	public function require_post(){
		
	}
	/**
	 * @http_method GET
	 */
	public function require_get(){
	
	}
}