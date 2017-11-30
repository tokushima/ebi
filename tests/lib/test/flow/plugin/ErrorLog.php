<?php
namespace test\flow\plugin;

class ErrorLog{
	public function flow_exception_log($pathinfo,$pattern,$ins,\Exception $e){
		$json = ['type'=>get_class($e),'path'=>$pathinfo,'pattern'=>$pattern,'action'=>(is_object($ins) ? get_class($ins) : ''),'exception'=>$e->getTraceAsString()];
		\ebi\Log::error(json_encode($json));
	}
}

