<?php
namespace ebi\flow\plugin;
/**
 * Xmlで出力するFlowプラグイン
 * @author tokushima
 */
class OutputXml{
	public function flow_output($array){
		$xml = new \ebi\Xml('result');
		$xml->add($array);
		
		\ebi\HttpHeader::send('Content-Type','application/xml');
		\ebi\HttpHeader::send_status(500);
		print($xml->get('UTF-8'));
	}
}