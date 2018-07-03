<?php
namespace ebi\flow\plugin;
/**
 * CORS (Cross-Origin Resource Sharing)
 * @author tokushima
 *
 */
class Cors{
	public function before_flow_action(){
		$origin_header = (new \ebi\Env())->get('HTTP_ORIGIN');
		
		/**
		 * @param string[] $origin 許可するURL
		 */
		$origin = \ebi\Conf::get('origin');

		if(empty($origin) && \ebi\Conf::get('debug',false) === true){
			$origin = [$origin_header];
		}
		if(!is_array($origin)){
			$origin = [$origin];
		}
		if(in_array($origin_header, $origin)){
			\ebi\HttpHeader::cors_origin($origin_header);
		}
	}
}