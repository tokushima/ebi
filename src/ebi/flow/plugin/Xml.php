<?php
namespace ebi\flow\plugin;
/**
 * Xmlで出力するFlowプラグイン
 * @author tokushima
 */
class Xml{
	/*
	 * @plugin ebi.Flow
	 */
	public function flow_output($array){
		$xml = new \ebi\Xml('result');
		$xml->add($array);
		
		\ebi\HttpHeader::send('Content-Type','application/xml');
		/**
		 * XMLのencodingに指定するエンコード名
		 */
		print($xml->get(\ebi\Conf::get('encoding')));
	}
	/**
	 * @plugin ebi.Flow
	 * @param \Exception $exception
	 */
	public function flow_exception(\Exception $exception){
		$xml = new \ebi\Xml('error');
		
		if(!($exception instanceof \ebi\Exceptions)){
			$exception = [''=>$exception];
		}
		foreach($exception as $g => $e){
			$class_name = get_class($e);
			$message = new \ebi\Xml('message',$e->getMessage());
			
			if(!empty($g)){
				$message->add('group',$g);
			}
			$message->add('type',basename(str_replace("\\",'/',$class_name)));
			$xml->add($message);
		}
		\ebi\HttpHeader::send('Content-Type','application/xml');
		print($xml->get(\ebi\Conf::get('encoding')));
	}
}