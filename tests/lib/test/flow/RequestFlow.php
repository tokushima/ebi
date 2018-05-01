<?php
namespace test\flow;


class RequestFlow extends \ebi\flow\Request{
	/**
	 * 前処理
	 * @request integer $zzz 前処理のリクエスト
	 */
	protected function __before__(){
		
	}
	
	/**
	 * aaa
	 * bbb
	 * 
	 * @request string $abc @['require'=>true]
	 * @request string $def @['require'=>true]
	 * @request integer $ghi
	 */
	public function require_vars(){
		$this->is_post();
	}
	
	/**
	 * アノテーションエラー
	 * @request string $abc @['require'=>true]
	 * @request string $def @['require'=>true']
	 * @request integer $ghi
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
	 * @context integer $abc
	 * @context integer $def
	 */
	public function get_vars(){
		return ['abc'=>123,'def'=>456];
	}
	
	/**
	 * メールを送信する
	 */
	public function sendmail(){
		$vars = ['abc'=>'ABC'];
		$mail = new \ebi\Mail();
		$mail->to("test@email.address");
		
		/**
		 * 
		 * @param string $abc ABCが出せる
		 */
		$mail->send_template('send.xml',$vars);
		
		/**
		 * メール送信拡張
		 * @param string $address
		 */
		self::call_class_plugin_funcs('plguin_sendmail',$address);
	}
	
	/**
	 * @request file $file1 @['require'=>true,'max'=>0.001]
	 */
	public function file_upload(){
		$req = new \ebi\Request();
		
		$file = $req->in_files('file1');
		
		return [
			'vars'=>$req->ar_vars(),
			'files'=>$req->ar_files(),
		];
	}
}