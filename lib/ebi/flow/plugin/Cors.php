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
		 * @param string $origin 許可するURL
		 */
		$origin = \ebi\Conf::get('origin','*');
		
		\ebi\HttpHeader::cors_origin($origin);
	}
}