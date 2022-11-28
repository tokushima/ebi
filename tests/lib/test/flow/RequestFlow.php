<?php
namespace test\flow;

/**
 * リクエストフロー
 * 様々な
 * @see https://google.com
 * @see \test\flow\RequestFlow::sendmail
 * @see \test\flow\RequestFlow
 */
class RequestFlow extends \ebi\flow\Request{	
	/**
	 * aaa
	 * bbb
	 * 
	 * @request string $abc @['require'=>true]
	 * @request string $def @['require'=>true]
	 * @request int $ghi
	 */
	public function require_vars(){
		$this->is_post();
	}
	
	/**
	 * アノテーションエラー
	 * @request string $abc @['require'=>true]
	 * @request string $def @['require'=>true']
	 * @request int $ghi
	 */
	public function require_vars_annotation_error(){
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
	
	/**
	 * @request email $email
	 */
	public function require_var_type(){
	
	}
	
	/**
	 * @context int $abc
	 * @context int $def
	 */
	public function get_vars(){
		return ['abc'=>123,'def'=>456];
	}
	
	/**
	 * @request file $file1 @['require'=>true,'max'=>0.001]
	 */
	public function file_upload(){
		$req = new \ebi\Request();
		
		return [
			'vars'=>$req->ar_vars(),
			'files'=>$req->ar_files(),
		];
	}
}