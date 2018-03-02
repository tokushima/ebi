<?php
namespace test\flow\plugin;

class ErrorLog{
	public function flow_exception_log($pathinfo,$pattern,$ins,\Exception $e){
		if(!($e instanceof \ebi\exception\UnauthorizedException)){
			$info = [
				'type'=>get_class($e),
				'type'=>$e->getMessage(),
				'path'=>$pathinfo,
				'pattern'=>$pattern,
				'action'=>(is_object($ins) ? get_class($ins) : ''),
				'exception'=>$e->getTraceAsString()
			];
			\ebi\Log::error($info);
		}
	}
}

