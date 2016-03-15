<?php
namespace ebi\flow\plugin;
/**
 * CORS (Cross-Origin Resource Sharing)
 * @author tokushima
 *
 */
class Cors{
	public function before_flow_action(){
		/**
		 * 許可するURL
		 * @param string[] $origin 許可するURL
		 */
		$urls = \ebi\Conf::get('origin',['*']);
		
		if(!is_array($urls)){
			$urls = [$urls];
		}
		\ebi\HttpHeader::cors_origin($urls);
	}
}