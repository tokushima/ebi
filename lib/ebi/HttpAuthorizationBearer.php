<?php
namespace ebi;

class HttpAuthorizationBearer{	
	/**
	 * ヘッダからtokenの取得
	 */
	public static function get_token(): ?string{
		$m = [];
		
		if(isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/((?i)Bearer(?-i)(\s)+)(.*)/',$_SERVER['HTTP_AUTHORIZATION'],$m)){
			return trim($m[3]);
		}
		return null;
	}
	
	/**
	 * create token (token68)
	 */
	public static function create_token(int $length): string{
		return \ebi\Code::rand('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~+/',$length);
	}
	
	/**
	 * エラーヘッダを設定
	 */
	public static function send_error_header(int $status_code, ?string $realm=null, ?string $description=null): void{
		\ebi\HttpHeader::send_status(\ebi\HttpHeader::status_string($status_code));
		$error = [];
		
		switch($status_code){
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