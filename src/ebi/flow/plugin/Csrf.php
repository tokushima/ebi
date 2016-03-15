<?php
namespace ebi\flow\plugin;
/**
 * CSRFトークン
 * @author tokushima
 */
class Csrf{
	private $token;
	
	/**
	 * @plugin ebi.Flow
	 * @throws \RuntimeException
	 */
	public function before_flow_action(){
		$req = new \ebi\Request();
		/**
		 * シークレットキー
		 */
		$secret_key = \ebi\Conf::get('secret_key',sha1(__FILE__));
		$session = new \ebi\Session();
		
		$csrftoken = $req->in_vars('csrftoken');
		$validtoken = $session->in_vars('validtoken');

		$this->token = md5(rand(1000,10000).time());
		$session->vars('validtoken',sha1($secret_key.$this->token));		
		
		if($req->is_post() && 
			($validtoken !== sha1($secret_key.$csrftoken))
		){
			\ebi\HttpHeader::send_status(403);
			throw new \RuntimeException('CSRF verification failed');
		}
	}
	/**
	 * @plugin ebi.Flow
	 * @param \ebi\flow\Request $req
	 */
	public function before_flow_action_request(\ebi\flow\Request $req){
		$req->vars('csrftoken',$this->token);
	}
	/**
	 * @plugin ebi.Template
	 * @param unknown $src
	 * @return Ambigous <string, string, mixed>|unknown
	 */
	public function after_template($src){
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