<?php
namespace ebi\flow\plugin;
/**
 * CORS (Cross-Origin Resource Sharing)
 * @author tokushima
 *
 */
class Cors{
	/**
	 * @plugin \ebi\Flow
	 */
	public function before_flow_action(){
		$env = new \ebi\Env();
		$request_origin = $env->get('HTTP_ORIGIN');
		$request_method = $env->get('HTTP_ACCESS_CONTROL_REQUEST_METHOD');
		$request_header = $env->get('HTTP_ACCESS_CONTROL_REQUEST_HEADERS');
		
		/**
		 * @param string[] $origin 許可するURL
		 */
		$origin = \ebi\Conf::get('origin');
		
		/**
		 * @param int $max_age プリフライトの応答をキャッシュする秒数
		 */
		$max_age = (int)\ebi\Conf::get('max_age',0);
		
		/**
		 * @param bool $debug ORIGINを常に許可する
		 */
		if(empty($origin) && \ebi\Conf::get('debug',false) === true){
			$origin = [$request_origin];
		}
		
		if(!is_array($origin)){
			$origin = [$origin];
		}
		if(in_array($request_origin, $origin)){
			\ebi\HttpHeader::send('Access-Control-Allow-Origin',$request_origin);
			\ebi\HttpHeader::send('Access-Control-Allow-Credentials','true');
			
			if(\ebi\Request::method() == 'OPTIONS' && $request_origin != '*'){
				if(!empty($request_method)){
					\ebi\HttpHeader::send('Access-Control-Allow-Methods',$request_method);
				}
				if(!empty($request_header)){
					\ebi\HttpHeader::send('Access-Control-Allow-Headers',$request_header);
				}
				if($max_age > 0){
					\ebi\HttpHeader::send('Access-Control-Max-Age',$max_age);
				}
				exit;
			}
		}
	}
}