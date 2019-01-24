<?php
namespace ebi\flow\plugin;
/**
 * CSRFトークン
 * @author tokushima
 */
class Csrf{
	private $token;
	
	public function before_flow_action(){
		$req = new \ebi\Request();
		$sess = new \ebi\Session();
		
		if($req->is_post()){
			if($req->in_vars('csrftoken') != $sess->in_vars('csrftoken')){
				\ebi\HttpHeader::send_status(403);
				throw new \ebi\exception\TokenMismatchException('CSRF verification failed');
			}
		}else{
			$this->token = md5(rand(1000,10000).time());
			$sess->vars('validtoken',$this->token);
		}
		
	}
// 	/**
// 	 * @plugin ebi.Flow
// 	 * @param \ebi\flow\Request $req
// 	 */
// 	public function before_flow_action_request(\ebi\flow\Request $req){
// 		/**
// 		 * @param string $secret_key シークレットキー
// 		 */
// 		$secret_key = \ebi\Conf::get('secret_key',sha1(__FILE__));
		
// 		if($req->is_post()){
// 			$validtoken = $req->in_sessions('validtoken');
// 			$req->rm_sessions('validtoken');
			
// 			if(empty($validtoken) || $validtoken != sha1($secret_key.$req->in_vars('csrftoken'))){
// 				\ebi\HttpHeader::send_status(403);
// 				throw new \ebi\exception\TokenMismatchException('CSRF verification failed');
// 			}
// 		}else{
// 			$this->token = md5(rand(1000,10000).time());
// 			$req->sessions('validtoken',sha1($secret_key.$this->token));
// 		}
// 	}
	
	/**
	 * @plugin ebi.Template
	 * @param string $src
	 * @return string
	 */
	public function after_template($src){
		if(empty($this->token)){
			return $src;
		}
		return \ebi\Xml::find_replace($src, 'form', function($xml){
			if($xml->in_attr('action') == '' || strpos($xml->in_attr('action'),'$t.map_url') !== false){
				$xml->escape(false);
				$xml->value(
					sprintf('<input type="hidden" name="csrftoken" value="%s" %s/>',
						$this->token,
						(($xml->in_attr('rt:ref') === 'true' || $xml->in_attr('rt:aref') === 'true') ? 'rt:ref="false"' : '')
					).$xml->value());
				return $xml;
			}
		});
	}
}