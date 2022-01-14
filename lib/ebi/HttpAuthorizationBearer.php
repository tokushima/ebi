<?php
namespace ebi;
/**
 * Bearer token
 * @author tokushima
 *
 */
class HttpAuthorizationBearer{
	
	
	/**
	 * ヘッダからtokenの取得
	 * @return string
	 */
	public static function get_token(){
		$m = [];
		
		if(isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/((?i)Bearer(?-i)(\s)+)(.*)/',$_SERVER['HTTP_AUTHORIZATION'],$m)){
			return trim($m[3]);
		}
		return null;
	}
	
	/**
	 * create token (token68)
	 * @param int $length
	 * @return string
	 */
	public static function create_token($length){
		return \ebi\Code::rand('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~+/',$length);
	}
	
	/**
	 * エラーヘッダ
	 * @param int $statuscode
	 * @param string $realm
	 * @param string $description
	 */
	public static function send_error_header($statuscode,$realm=null,$description=null){
		\ebi\HttpHeader::send_status(\ebi\HttpHeader::status_string($statuscode));
		$error = [];
		
		switch($statuscode){
			case 401:
				$error[] = 'error="invalid_token"';
				break;
			case 400:
				$error[] = 'error="invalid_request"';
				break;
			case 403:
				$error[] = 'error="insufficient_scope"';
				break;
			default:
		}
		if(!empty($realm)){
			$error[] = sprintf('realm="%s"',$realm);
		}
		if(!empty($description)){
			$error[] = sprintf('error_description="%s"',$description);
		}
		if(!empty($error)){
			\ebi\HttpHeader::send('WWW-Authenticate','Bearer '.implode(', ',$error));
		}
	}
}