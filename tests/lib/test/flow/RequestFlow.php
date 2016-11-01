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
	
	/**
	 * @request email $email
	 */
	public function require_var_type(){
	
	}
	
	/**
	 * @context integer $abc
	 * @context integer $def
	 */
	public function get_vars(){
		return ['abc'=>123,'def'=>456];
	}
	
	public function sendmail(){
		$vars = ['abc'=>'ABC'];
		$mail = new \ebi\Mail();
		$mail->to("test@email.address");
		$mail->send_template('send.xml',$vars);
	}
}