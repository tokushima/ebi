<?php
namespace ebi\flow\plugin;
/**
 * Jsonpで出力するFlowプラグイン
 * @author tokushima
 */
class OutputJsonp{
	public function flow_output($array){
		$func_name = 'callback';
		
		$req = new \ebi\Request();		
		if($req->is_vars('callback')){
			$func_name = $req->in_vars('callback');
		}
		\ebi\HttpHeader::send('Content-Type','text/javascript');
		$json = json_encode(array('result'=>$array),true);
		print($func_name.'('.$json.')');
	}
}