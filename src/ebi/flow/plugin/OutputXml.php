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
		
		header(sprintf('Content-Type: application/xml'));
		print($xml->get('UTF-8'));
	}
}